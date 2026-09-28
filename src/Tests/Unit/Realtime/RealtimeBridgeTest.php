<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Realtime;

use PHPUnit\Framework\TestCase;
use Proto\Realtime\RealtimeBridge;
use Proto\Realtime\Server\SseFormat;
use Proto\Realtime\Server\StreamDescriptor;
use Proto\Realtime\Server\RealtimeConfig;
use Proto\Realtime\Server\ConnectionRegistry;

/**
 * RealtimeBridgeTest
 *
 * PHP side of the realtime server (secret check, authorize and hydrate
 * answers) and the pure pieces of the server (wire format, descriptor
 * validation, config).
 *
 * @package Proto\Tests\Unit\Realtime
 */
final class RealtimeBridgeTest extends TestCase
{
	private const SECRET = 'test-secret-0123456789-abcdefghijklmnop';

	/**
	 * @return void
	 */
	public function testModeRequiresMatchingSecret(): void
	{
		$server = [
			RealtimeBridge::SECRET_HEADER => self::SECRET,
			RealtimeBridge::MODE_HEADER => 'authorize'
		];

		$this->assertSame('authorize', RealtimeBridge::mode($server, self::SECRET));
		$this->assertNull(RealtimeBridge::mode($server, self::SECRET . 'x'));
		$this->assertNull(RealtimeBridge::mode($server, ''), 'No configured secret never trusts a header.');
		$this->assertNull(RealtimeBridge::mode([RealtimeBridge::MODE_HEADER => 'authorize'], self::SECRET));
		$this->assertNull(RealtimeBridge::mode([
			RealtimeBridge::SECRET_HEADER => self::SECRET,
			RealtimeBridge::MODE_HEADER => 'stream'
		], self::SECRET));
	}

	/**
	 * @return void
	 */
	public function testDescribeFiltersChannelsAndPicksMode(): void
	{
		$viewer = (object)['userId' => '42', 'sessionId' => 'abc', 'sseClient' => 'tab12345'];
		$described = RealtimeBridge::describe(
			['conversation:9:messages', 'bad channel!', 'user:3:status', 'conversation:9:messages'],
			fn() => null,
			RealtimeBridge::HYDRATE_SHARED,
			$viewer
		);

		$this->assertTrue($described['allow']);
		$this->assertSame(['conversation:9:messages', 'user:3:status'], $described['channels']);
		$this->assertSame('shared', $described['hydrate']);
		$this->assertSame('42', $described['userId']);

		$raw = RealtimeBridge::describe('notification:user:42', null, RealtimeBridge::HYDRATE_VIEWER, $viewer);
		$this->assertSame('raw', $raw['hydrate'], 'No callback means nothing to run in PHP.');

		$none = RealtimeBridge::describe('bad channel!', null, RealtimeBridge::HYDRATE_VIEWER, $viewer);
		$this->assertFalse($none['allow']);
	}

	/**
	 * @return void
	 */
	public function testHydrateRunsCallbackLikeRedisServerEvents(): void
	{
		$body = (string)json_encode(['channel' => 'vehicle:1:bids', 'message' => '{"id":5,"action":"merge"}']);

		$result = RealtimeBridge::hydrate(
			fn(string $channel, array $message) => ['merge' => [$message['id']], 'channel' => $channel],
			$body
		);
		$this->assertSame(['result' => ['merge' => [5], 'channel' => 'vehicle:1:bids'], 'close' => false], $result);

		$this->assertSame(['result' => null, 'close' => false], RealtimeBridge::hydrate(fn() => null, $body));
		$this->assertSame(['result' => null, 'close' => true], RealtimeBridge::hydrate(fn() => false, $body));
		$this->assertSame(
			['result' => ['id' => 5, 'action' => 'merge'], 'close' => false],
			RealtimeBridge::hydrate(null, $body)
		);
	}

	/**
	 * @return void
	 */
	public function testSseFramesMatchPhpStreams(): void
	{
		$this->assertSame("event: message\ndata: {\"a\":1}\n\n", SseFormat::message('{"a":1}'));
		$this->assertSame("event: message\ndata: line1\ndata: line2\n\n", SseFormat::message("line1\nline2"));
		$this->assertSame(": heartbeat\n\n", SseFormat::comment('heartbeat'));
		$this->assertSame("retry: 3000\n\n", SseFormat::retry(3000));
	}

	/**
	 * @return void
	 */
	public function testDescriptorRejectsUnsafeAnswers(): void
	{
		$this->assertNull(StreamDescriptor::fromArray(['allow' => false, 'channels' => ['a:b']]));
		$this->assertNull(StreamDescriptor::fromArray(['allow' => true, 'channels' => ['bad channel']]));
		$this->assertNull(
			StreamDescriptor::fromArray(['allow' => true, 'channels' => ['sse:user:close:1']]),
			'Control channels are never granted by upstream.'
		);

		$descriptor = StreamDescriptor::fromArray([
			'allow' => true,
			'channels' => ['a:b', 'a:b'],
			'hydrate' => 'weird',
			'userId' => '../../x',
			'sseClient' => 'short'
		]);
		$this->assertNotNull($descriptor);
		$this->assertSame(['a:b'], $descriptor->channels);
		$this->assertSame('viewer', $descriptor->hydrate);
		$this->assertSame('guest', $descriptor->userId);
		$this->assertSame('', $descriptor->sseClient);
		$this->assertFalse($descriptor->hasUser());
	}

	/**
	 * Same key as RedisServerEvents so PHP and realtime streams replace
	 * each other.
	 *
	 * @return void
	 */
	public function testRegistryKeyMatchesPhpStreams(): void
	{
		$descriptor = new StreamDescriptor(['a:b'], 'raw', '42', 'sess', 'tab12345');
		$this->assertSame(
			'sse:connection:42:sess:' . md5('/api/notification/sync') . ':tab12345',
			ConnectionRegistry::key($descriptor, '/api/notification/sync', 'sse_x')
		);

		$noTab = new StreamDescriptor(['a:b'], 'raw', '42', '', '');
		$this->assertSame(
			'sse:connection:42:nosession:' . md5('/p') . ':sse_x',
			ConnectionRegistry::key($noTab, '/p', 'sse_x')
		);
	}

	/**
	 * @return void
	 */
	public function testConfigFromEnv(): void
	{
		$config = RealtimeConfig::fromEnv(
			(object)['secret' => self::SECRET, 'upstream' => 'http://web/'],
			(object)['connection' => (object)['host' => 'redis', 'port' => 6379, 'password' => 'p@ss']],
			['port' => '9200']
		);

		$this->assertSame('redis://:p%40ss@redis:6379', $config->redisUri);
		$this->assertSame('http://web', $config->upstream);
		$this->assertSame(9200, $config->port);

		$this->expectException(\InvalidArgumentException::class);
		RealtimeConfig::fromEnv((object)['secret' => 'short', 'upstream' => 'http://web'], null);
	}
}
