<?php declare(strict_types=1);
namespace Proto\Http\ServerEvents;

/**
 * StreamWriter
 *
 * Safe write helpers for SSE streams.
 *
 * `echo` is unusable for SSE because it never reports failure when the
 * underlying socket is closed — heartbeats happily write into a dead pipe
 * forever, leaking PHP-FPM workers. This class wraps writes through
 * `fwrite(php://output, ...)` so callers can detect and react to broken
 * pipes immediately.
 *
 * @package Proto\Http\ServerEvents
 */
final class StreamWriter
{
	/**
	 * Cached writable handle to `php://output`. Lazily opened on first use.
	 *
	 * @var resource|null
	 */
	private static $handle = null;

	/**
	 * Whether the most recent write succeeded.
	 *
	 * @var bool
	 */
	private static bool $lastOk = true;

	/**
	 * Returns the cached `php://output` handle, opening it if needed.
	 *
	 * @return resource|null
	 */
	private static function handle()
	{
		if (self::$handle !== null && is_resource(self::$handle))
		{
			return self::$handle;
		}

		$h = @fopen('php://output', 'w');
		self::$handle = ($h !== false) ? $h : null;
		return self::$handle;
	}

	/**
	 * Writes raw bytes to the client. Returns true on success, false on
	 * broken pipe or any other write failure.
	 *
	 * @param string $data Raw bytes to write.
	 * @return bool
	 */
	public static function write(string $data): bool
	{
		if ($data === '')
		{
			return self::$lastOk;
		}

		$h = self::handle();
		if ($h === null)
		{
			self::$lastOk = false;
			return false;
		}

		$len = strlen($data);
		$written = 0;

		while ($written < $len)
		{
			$result = @fwrite($h, substr($data, $written));
			if ($result === false || $result === 0)
			{
				self::$lastOk = false;
				return false;
			}
			$written += $result;
		}

		self::$lastOk = true;
		return true;
	}

	/**
	 * Flushes any buffered output. Returns true on success.
	 *
	 * @return bool
	 */
	public static function flush(): bool
	{
		$h = self::handle();
		if ($h !== null && @fflush($h) === false)
		{
			self::$lastOk = false;
			return false;
		}

		// Walk down output buffer levels (defensive — SSE setup turns these
		// off but app code or extensions may have re-enabled one).
		$levels = ob_get_level();
		for ($i = 0; $i < $levels; $i++)
		{
			@ob_flush();
		}

		@flush();
		return self::$lastOk;
	}

	/**
	 * Writes data and flushes in one call. Returns true on success.
	 *
	 * @param string $data
	 * @return bool
	 */
	public static function writeAndFlush(string $data): bool
	{
		if (!self::write($data))
		{
			return false;
		}
		return self::flush();
	}

	/**
	 * True if the script's view of the connection is still healthy. Combines
	 * the last write result with PHP's connection_status() flag (which is
	 * unreliable behind proxies on its own, but is meaningful once a real
	 * write has been attempted).
	 *
	 * @return bool
	 */
	public static function isAlive(): bool
	{
		if (!self::$lastOk)
		{
			return false;
		}

		if (function_exists('connection_status') && connection_status() !== CONNECTION_NORMAL)
		{
			return false;
		}

		if (function_exists('connection_aborted') && connection_aborted() === 1)
		{
			return false;
		}

		return true;
	}

	/**
	 * Resets writer state. Intended for testing.
	 */
	public static function reset(): void
	{
		if (is_resource(self::$handle))
		{
			@fclose(self::$handle);
		}
		self::$handle = null;
		self::$lastOk = true;
	}
}
