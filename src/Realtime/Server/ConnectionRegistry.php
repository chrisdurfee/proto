<?php declare(strict_types=1);
namespace Proto\Realtime\Server;

use Amp\Redis\Command\Option\SetOptions;
use Amp\Redis\RedisClient;
use Psr\Log\LoggerInterface;

/**
 * ConnectionRegistry
 *
 * The one-stream-per-tab rule, shared with PHP-FPM streams. Uses the
 * same Redis key and close signal as `RedisServerEvents`, so a browser
 * that reconnects to the other kind of stream still replaces its old one.
 *
 * @package Proto\Realtime\Server
 */
final class ConnectionRegistry
{
	/**
	 * @param RedisClient $redis
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		private readonly RedisClient $redis,
		private readonly LoggerInterface $logger
	)
	{
	}

	/**
	 * Same format as `RedisServerEvents::getConnectionKey()`.
	 *
	 * @param StreamDescriptor $descriptor
	 * @param string $path
	 * @param string $connectionId
	 * @return string
	 */
	public static function key(StreamDescriptor $descriptor, string $path, string $connectionId): string
	{
		$sessionId = $descriptor->sessionId !== '' ? $descriptor->sessionId : 'nosession';
		$clientId = $descriptor->sseClient !== '' ? $descriptor->sseClient : $connectionId;

		return "sse:connection:{$descriptor->userId}:{$sessionId}:" . md5($path) . ":{$clientId}";
	}

	/**
	 * Register this connection and close the one it replaces.
	 *
	 * @param string $key
	 * @param string $connectionId
	 * @param int $ttl
	 * @return void
	 */
	public function claim(string $key, string $connectionId, int $ttl): void
	{
		try
		{
			$previous = $this->redis->get($key);
			$this->redis->set($key, $connectionId, (new SetOptions())->withTtl($ttl));

			if ($previous !== null && $previous !== $connectionId)
			{
				// PHP streams also poll this key as a fallback.
				$this->redis->set("sse:close:{$previous}", '1', (new SetOptions())->withTtl(5));
				$this->redis->publish("sse:close:{$previous}", 'close');
			}
		}
		catch (\Throwable $e)
		{
			$this->logger->warning('Could not register stream', ['error' => $e->getMessage()]);
		}
	}

	/**
	 * Remove the registration if it is still ours.
	 *
	 * @param string $key
	 * @param string $connectionId
	 * @return void
	 */
	public function release(string $key, string $connectionId): void
	{
		try
		{
			if ($this->redis->get($key) === $connectionId)
			{
				$this->redis->delete($key);
			}
		}
		catch (\Throwable)
		{
			// The TTL cleans up.
		}
	}
}
