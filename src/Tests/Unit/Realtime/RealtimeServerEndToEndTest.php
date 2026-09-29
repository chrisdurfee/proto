<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Realtime;

use Amp\ByteStream\ReadableStream;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request as ClientRequest;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\SocketHttpServer;
use Amp\Redis\RedisClient;
use PHPUnit\Framework\TestCase;
use Proto\Realtime\Server\RealtimeConfig;
use Proto\Realtime\Server\RealtimeServer;
use Proto\Realtime\Server\StderrLogger;
use Psr\Log\NullLogger;
use function Amp\delay;
use function Amp\Redis\createRedisClient;

/**
 * RealtimeServerEndToEndTest
 *
 * Boots the real realtime server against real Redis and a fake PHP
 * upstream, then reads the raw SSE bytes a browser would get.
 *
 * Opt-in (needs Redis): PROTO_REALTIME_REDIS_URI=redis://:pass@host:6379
 *
 * @package Proto\Tests\Unit\Realtime
 */
final class RealtimeServerEndToEndTest extends TestCase
{
	private const SECRET = 'e2e-secret-0123456789-abcdefghijklmnopq';

	private ?SocketHttpServer $upstream = null;
	private ?RealtimeServer $realtime = null;
	private RedisClient $redis;
	private string $base = '';
	private string $prefix = '';

	/**
	 * Hydrate calls seen by the fake upstream.
	 *
	 * @var int
	 */
	private int $hydrateCalls = 0;

	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		$uri = getenv('PROTO_REALTIME_REDIS_URI');
		if (!is_string($uri) || $uri === '')
		{
			$this->markTestSkipped('Set PROTO_REALTIME_REDIS_URI to run the realtime end-to-end test.');
		}

		$this->redis = createRedisClient($uri);
		$this->prefix = 'e2e' . bin2hex(random_bytes(4));

		$this->upstream = SocketHttpServer::createForDirectAccess(new NullLogger(), enableCompression: false);
		$this->upstream->expose('127.0.0.1:0');
		$this->upstream->start(new ClosureRequestHandler(fn(Request $request) => $this->fakePhp($request)), new DefaultErrorHandler());
		$upstreamAddress = $this->upstream->getServers()[0]->getAddress()->toString();

		$config = new RealtimeConfig(
			secret: self::SECRET,
			upstream: 'http://' . $upstreamAddress,
			redisUri: $uri,
			host: '127.0.0.1',
			port: 0,
			heartbeatSeconds: 1
		);
		$this->realtime = new RealtimeServer($config, new StderrLogger((string)(getenv('PROTO_REALTIME_TEST_LOG') ?: 'error')));
		$this->base = 'http://' . $this->realtime->start();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		$this->realtime?->stop();
		$this->upstream?->stop();
	}

	/**
	 * Raw streams forward published payloads as `event: message` frames.
	 *
	 * @return void
	 */
	public function testRawStreamForwardsMessages(): void
	{
		$body = $this->open('/raw/sync');
		$this->readUntil($body, ": connected\n\n");

		$this->publishSoon("{$this->prefix}:raw", '{"merge":[1],"refresh":false}');
		$frames = $this->readUntil($body, "\n\n", 'event: message');

		$this->assertStringContainsString("event: message\ndata: {\"merge\":[1],\"refresh\":false}\n\n", $frames);
	}

	/**
	 * Viewer streams call PHP per message and keep publish order even
	 * when earlier hydrations are slower.
	 *
	 * @return void
	 */
	public function testViewerHydrationKeepsOrder(): void
	{
		$body = $this->open('/viewer/sync');
		$this->readUntil($body, ": connected\n\n");

		\Amp\async(function (): void
		{
			delay(0.1);
			foreach ([1, 2, 3] as $id)
			{
				$this->redis->publish("{$this->prefix}:viewer", (string)json_encode(['id' => $id]));
			}
		});

		$frames = '';
		while (substr_count($frames, 'event: message') < 3)
		{
			$frames .= $this->readUntil($body, "\n\n", 'event: message');
		}

		preg_match_all('/"id":(\d)/', $frames, $matches);
		$this->assertSame(['1', '2', '3'], $matches[1]);
		$this->assertStringContainsString('"viewer":"alice"', $frames);
		$this->assertSame(3, $this->hydrateCalls);
	}

	/**
	 * Shared streams hydrate once per message for every viewer of the URL.
	 *
	 * @return void
	 */
	public function testSharedHydrationRunsOncePerMessage(): void
	{
		$first = $this->open('/shared/sync');
		$second = $this->open('/shared/sync');
		$this->readUntil($first, ": connected\n\n");
		$this->readUntil($second, ": connected\n\n");

		$this->publishSoon("{$this->prefix}:shared", '{"id":9}');
		$this->assertStringContainsString('"id":9', $this->readUntil($first, "\n\n", 'event: message'));
		$this->assertStringContainsString('"id":9', $this->readUntil($second, "\n\n", 'event: message'));
		$this->assertSame(1, $this->hydrateCalls);
	}

	/**
	 * PHP's refusal reaches the browser unchanged; nothing is streamed.
	 *
	 * @return void
	 */
	public function testDeniedStreamRelaysPhpResponse(): void
	{
		$response = HttpClientBuilder::buildDefault()->request(new ClientRequest($this->base . '/deny/sync'));
		$this->assertSame(403, $response->getStatus());
		$this->assertStringContainsString('Forbidden', $response->getBody()->buffer());
	}

	/**
	 * Publishing on the user kick channel ends the stream (logout).
	 *
	 * @return void
	 */
	public function testUserCloseChannelEndsStream(): void
	{
		$body = $this->open('/raw/sync');
		$this->readUntil($body, ": connected\n\n");

		$this->publishSoon("sse:user:close:{$this->prefix}", 'close');

		$rest = '';
		while (($chunk = $body->read()) !== null)
		{
			$rest .= $chunk;
		}
		$this->assertStringNotContainsString('event: message', $rest);
	}

	/**
	 * Heartbeats keep idle streams alive.
	 *
	 * @return void
	 */
	public function testHeartbeats(): void
	{
		$body = $this->open('/raw/sync');
		$this->assertStringContainsString(': heartbeat', $this->readUntil($body, ": heartbeat\n\n"));
	}

	/**
	 * Production regression: with the default 15s heartbeat, Amp's default
	 * 15s idle timeout dropped every stream at ~14.5s and browsers looped.
	 * The idle timeout now always exceeds the heartbeat.
	 *
	 * @return void
	 */
	public function testIdleStreamSurvivesPastDefaultDriverTimeout(): void
	{
		$uri = (string)getenv('PROTO_REALTIME_REDIS_URI');
		$upstream = 'http://' . $this->upstream->getServers()[0]->getAddress()->toString();
		$slow = new RealtimeServer(
			new RealtimeConfig(
				secret: self::SECRET,
				upstream: $upstream,
				redisUri: $uri,
				host: '127.0.0.1',
				port: 0,
				heartbeatSeconds: 15
			),
			new StderrLogger('error')
		);
		$base = 'http://' . $slow->start();

		try
		{
			$request = new ClientRequest($base . '/raw/sync');
			$request->setTransferTimeout(40);
			$request->setInactivityTimeout(40);
			$body = HttpClientBuilder::buildDefault()->request($request)->getBody();

			$started = microtime(true);
			$seen = '';
			while (!str_contains($seen, ': heartbeat') && microtime(true) - $started < 20)
			{
				$chunk = $body->read();
				if ($chunk === null)
				{
					break;
				}
				$seen .= $chunk;
			}

			$this->assertStringContainsString(': heartbeat', $seen, 'Stream closed before its first heartbeat.');
			$this->assertGreaterThan(14.5, microtime(true) - $started);

			// Still open after crossing Amp's old 15s limit.
			delay(2);
			$this->redis->publish("{$this->prefix}:raw", '{"after":"timeout"}');
			$more = '';
			while (!str_contains($more, 'after') && ($chunk = $body->read()) !== null)
			{
				$more .= $chunk;
			}
			$this->assertStringContainsString('"after":"timeout"', $more);
		}
		finally
		{
			$slow->stop();
		}
	}

	/**
	 * @param string $path
	 * @return ReadableStream
	 */
	private function open(string $path): ReadableStream
	{
		$request = new ClientRequest($this->base . $path);
		$request->setHeader('cookie', 'session=alice');
		$request->setTransferTimeout(30);
		$request->setInactivityTimeout(30);

		$response = HttpClientBuilder::buildDefault()->request($request);
		$this->assertSame(200, $response->getStatus());
		$this->assertSame('text/event-stream', $response->getHeader('content-type'));
		return $response->getBody();
	}

	/**
	 * Read until `$marker` appears (after `$after`, when given).
	 *
	 * @param ReadableStream $body
	 * @param string $marker
	 * @param string $after
	 * @return string
	 */
	private function readUntil(ReadableStream $body, string $marker, string $after = ''): string
	{
		$buffer = '';
		$deadline = microtime(true) + 5;
		while (microtime(true) < $deadline)
		{
			$start = $after === '' ? 0 : strpos($buffer, $after);
			if ($start !== false && strpos($buffer, $marker, (int)$start) !== false)
			{
				return $buffer;
			}

			$chunk = $body->read();
			if ($chunk === null)
			{
				break;
			}
			$buffer .= $chunk;
		}

		$this->fail("Did not see " . json_encode($marker) . " in stream: " . json_encode($buffer));
	}

	/**
	 * @param string $channel
	 * @param string $message
	 * @return void
	 */
	private function publishSoon(string $channel, string $message): void
	{
		\Amp\async(function () use ($channel, $message): void
		{
			delay(0.1);
			$this->redis->publish($channel, $message);
		});
	}

	/**
	 * Stand-in for the PHP app answering through RealtimeBridge.
	 *
	 * @param Request $request
	 * @return Response
	 */
	private function fakePhp(Request $request): Response
	{
		if ($request->getHeader('x-proto-realtime') !== self::SECRET)
		{
			return new Response(401, [], 'no secret');
		}

		$path = $request->getUri()->getPath();
		if ($path === '/deny/sync')
		{
			return new Response(403, ['content-type' => 'application/json'], '{"message":"Forbidden"}');
		}

		$kind = explode('/', trim($path, '/'))[0];
		if ($request->getHeader('x-proto-realtime-mode') === 'hydrate')
		{
			$this->hydrateCalls++;
			$input = json_decode($request->getBody()->buffer(), true);
			$message = json_decode((string)$input['message'], true);

			// Earlier messages answer slower, to prove delivery stays ordered.
			delay(max(0, 0.05 * (4 - (int)($message['id'] ?? 0))));
			$viewer = str_replace('session=', '', (string)$request->getHeader('cookie'));

			return new Response(200, ['content-type' => 'application/json'], (string)json_encode([
				'result' => ['id' => $message['id'] ?? null, 'viewer' => $viewer],
				'close' => false
			]));
		}

		return new Response(200, ['content-type' => 'application/json'], (string)json_encode([
			'allow' => true,
			'channels' => ["{$this->prefix}:{$kind}"],
			'hydrate' => ['raw' => 'raw', 'viewer' => 'viewer', 'shared' => 'shared'][$kind] ?? 'viewer',
			'userId' => $this->prefix,
			'sessionId' => 'alice',
			'sseClient' => ''
		]));
	}
}
