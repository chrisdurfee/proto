<?php declare(strict_types=1);
namespace Proto\Cache;

use Proto\Cache\Drivers\RedisDriver;
use Throwable;

/**
 * EventBuffer
 *
 * Redis-backed FIFO buffer for tolerable-loss, high-volume events
 * (e.g. analytics impressions, click tracking). Hot request paths push
 * small JSON payloads here instead of writing to the database
 * synchronously; a scheduled routine drains the list in bounded batches
 * and persists them.
 *
 * Failure semantics: every method degrades gracefully. If the Redis
 * extension is missing, the server is unreachable, or a command fails,
 * push() returns false (callers should fall back to synchronous
 * persistence), drain() returns an empty array, and size() returns 0.
 * The connection is attempted once per process and the result memoized.
 *
 * Example:
 *
 *   // Hot path
 *   if (!EventBuffer::push('ad-events', $payload))
 *   {
 *       $this->persistSynchronously($payload);
 *   }
 *
 *   // Scheduled flush
 *   $events = EventBuffer::drain('ad-events', 5000);
 *
 * @package Proto\Cache
 */
class EventBuffer extends RedisDriver
{
	/**
	 * Redis key prefix for buffer lists.
	 *
	 * @var string
	 */
	protected const KEY_PREFIX = 'buffer:events:';

	/**
	 * Memoized connected instance.
	 *
	 * @var EventBuffer|null
	 */
	protected static ?EventBuffer $instance = null;

	/**
	 * True after a failed connection attempt so we never retry per-request.
	 *
	 * @var bool
	 */
	protected static bool $unavailable = false;

	/**
	 * Appends an event payload to the channel's buffer.
	 *
	 * @param string $channel Logical event channel name.
	 * @param array $payload JSON-serializable event data.
	 * @return bool False when the buffer is unavailable or the push failed.
	 */
	public static function push(string $channel, array $payload): bool
	{
		$buffer = static::instance();
		if ($buffer === null)
		{
			return false;
		}

		$encoded = json_encode($payload);
		if ($encoded === false)
		{
			return false;
		}

		try
		{
			return $buffer->rPush(static::key($channel), $encoded) > 0;
		}
		catch (Throwable)
		{
			return false;
		}
	}

	/**
	 * Removes and returns up to $max buffered events in FIFO order.
	 *
	 * @param string $channel Logical event channel name.
	 * @param int $max Maximum number of events to drain.
	 * @return array<int, array> Decoded event payloads.
	 */
	public static function drain(string $channel, int $max = 1000): array
	{
		$buffer = static::instance();
		if ($buffer === null || $max <= 0)
		{
			return [];
		}

		try
		{
			$items = $buffer->lPop(static::key($channel), $max);
		}
		catch (Throwable)
		{
			return [];
		}

		$events = [];
		foreach ($items as $item)
		{
			$decoded = json_decode((string)$item, true);
			if (is_array($decoded))
			{
				$events[] = $decoded;
			}
		}

		return $events;
	}

	/**
	 * Current number of buffered events on the channel.
	 *
	 * @param string $channel Logical event channel name.
	 * @return int
	 */
	public static function size(string $channel): int
	{
		$buffer = static::instance();
		if ($buffer === null)
		{
			return 0;
		}

		try
		{
			return $buffer->lLen(static::key($channel));
		}
		catch (Throwable)
		{
			return 0;
		}
	}

	/**
	 * Builds the Redis key for a channel.
	 *
	 * @param string $channel Logical event channel name.
	 * @return string
	 */
	protected static function key(string $channel): string
	{
		return static::KEY_PREFIX . $channel;
	}

	/**
	 * Lazily connects and memoizes the buffer instance.
	 *
	 * @return EventBuffer|null Null when Redis is unavailable.
	 */
	protected static function instance(): ?EventBuffer
	{
		if (static::$unavailable)
		{
			return null;
		}

		if (static::$instance !== null)
		{
			return static::$instance;
		}

		try
		{
			$buffer = new static();
			if (!$buffer->isSupported() || !isset($buffer->db))
			{
				static::$unavailable = true;
				return null;
			}

			static::$instance = $buffer;
			return $buffer;
		}
		catch (Throwable)
		{
			static::$unavailable = true;
			return null;
		}
	}
}
