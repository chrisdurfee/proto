<?php declare(strict_types=1);
namespace Proto\Realtime\Server;

/**
 * RealtimeConfig
 *
 * Settings for the realtime server, read once at startup from the app's
 * `.env` (`realtime` block, Redis from `cache.connection`) with CLI
 * overrides on top.
 *
 * ```json
 * "realtime": {
 *     "secret": "long random string shared with PHP",
 *     "upstream": "http://127.0.0.1:8080",
 *     "host": "0.0.0.0",
 *     "port": 9100
 * }
 * ```
 *
 * @package Proto\Realtime\Server
 */
final class RealtimeConfig
{
	/**
	 * @param string $secret Shared with PHP (`realtime.secret`).
	 * @param string $upstream Base URL of the PHP app, e.g. http://web:80.
	 * @param string $redisUri e.g. redis://:password@redis:6379
	 * @param string $host
	 * @param int $port
	 * @param bool $upstreamVerifyTls
	 * @param int $heartbeatSeconds
	 * @param int $maxDurationSeconds
	 * @param int $reauthorizeSeconds
	 * @param int $maxPendingWrites Frames queued for one client before it is dropped as too slow.
	 * @param float $upstreamTimeoutSeconds
	 * @param int $retryMilliseconds
	 * @param array<int, string> $trustedProxies IPs or CIDR ranges allowed to set X-Forwarded-For.
	 * @param int $idleTimeoutSeconds Connection dropped after this long with no bytes written. Must exceed $heartbeatSeconds.
	 * @param int $upstreamConcurrency Calls into PHP (authorize + hydrate) in flight at once; the rest wait.
	 * @param int $upstreamMaxQueued Hydrate calls allowed to wait; past this, messages are dropped instead of piling up.
	 */
	public function __construct(
		public readonly string $secret,
		public readonly string $upstream,
		public readonly string $redisUri,
		public readonly string $host = '0.0.0.0',
		public readonly int $port = 9100,
		public readonly bool $upstreamVerifyTls = true,
		public readonly int $heartbeatSeconds = 15,
		public readonly int $maxDurationSeconds = 1800,
		public readonly int $reauthorizeSeconds = 300,
		public readonly int $maxPendingWrites = 256,
		public readonly float $upstreamTimeoutSeconds = 5.0,
		public readonly int $retryMilliseconds = 3000,
		public readonly array $trustedProxies = ['127.0.0.1', '::1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
		public readonly int $idleTimeoutSeconds = 60,
		public readonly int $upstreamConcurrency = 32,
		public readonly int $upstreamMaxQueued = 2048
	)
	{
		if ($upstreamConcurrency < 1 || $upstreamMaxQueued < 0)
		{
			throw new \InvalidArgumentException('realtime.upstreamConcurrency must be at least 1 and upstreamMaxQueued at least 0.');
		}

		if ($idleTimeoutSeconds <= $heartbeatSeconds)
		{
			// The HTTP driver drops a connection that writes nothing for
			// this long; a heartbeat at or past it kills every idle stream.
			throw new \InvalidArgumentException('realtime.idleTimeoutSeconds must be greater than heartbeatSeconds.');
		}

		if (strlen($secret) < 32)
		{
			throw new \InvalidArgumentException('realtime.secret must be at least 32 characters.');
		}

		if (!preg_match('#^https?://#', $upstream))
		{
			throw new \InvalidArgumentException('realtime.upstream must be an http(s) URL of the PHP app.');
		}
	}

	/**
	 * Build from the app's `realtime` and `cache` env blocks.
	 *
	 * @param object|null $realtime
	 * @param object|null $cache
	 * @param array<string, string> $overrides CLI flags (host, port, upstream)
	 * @return self
	 */
	public static function fromEnv(?object $realtime, ?object $cache, array $overrides = []): self
	{
		$realtime ??= (object)[];
		$connection = $cache->connection ?? null;
		$redisUri = (string)($realtime->redisUri ?? '');
		if ($redisUri === '')
		{
			$password = (string)($connection->password ?? '');
			$redisUri = sprintf(
				'redis://%s%s:%d',
				$password !== '' ? ':' . rawurlencode($password) . '@' : '',
				(string)($connection->host ?? '127.0.0.1'),
				(int)($connection->port ?? 6379)
			);
		}

		$heartbeat = max(1, (int)($realtime->heartbeatSeconds ?? 15));

		return new self(
			secret: (string)($realtime->secret ?? ''),
			upstream: rtrim((string)($overrides['upstream'] ?? $realtime->upstream ?? ''), '/'),
			redisUri: $redisUri,
			host: (string)($overrides['host'] ?? $realtime->host ?? '0.0.0.0'),
			port: (int)($overrides['port'] ?? $realtime->port ?? 9100),
			upstreamVerifyTls: (bool)($realtime->upstreamVerifyTls ?? true),
			heartbeatSeconds: $heartbeat,
			maxDurationSeconds: max(30, (int)($realtime->maxDurationSeconds ?? 1800)),
			reauthorizeSeconds: max(30, (int)($realtime->reauthorizeSeconds ?? 300)),
			maxPendingWrites: max(8, (int)($realtime->maxPendingWrites ?? 256)),
			upstreamTimeoutSeconds: max(0.5, (float)($realtime->upstreamTimeoutSeconds ?? 5.0)),
			retryMilliseconds: max(0, (int)($realtime->retryMilliseconds ?? 3000)),
			trustedProxies: is_array($realtime->trustedProxies ?? null)
				? array_values(array_map('strval', $realtime->trustedProxies))
				: ['127.0.0.1', '::1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
			// Default: four heartbeats of slack, never under 60s.
			idleTimeoutSeconds: max($heartbeat + 1, (int)($realtime->idleTimeoutSeconds ?? max(60, $heartbeat * 4))),
			upstreamConcurrency: max(1, (int)($realtime->upstreamConcurrency ?? 32)),
			upstreamMaxQueued: max(0, (int)($realtime->upstreamMaxQueued ?? 2048))
		);
	}
}
