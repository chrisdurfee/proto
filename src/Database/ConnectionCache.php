<?php declare(strict_types=1);

namespace Proto\Database;

/**
 * ConnectionCache
 *
 * Stores database connections to prevent multiple connections
 * to the same database.
 *
 * @package Proto\Database
 */
final class ConnectionCache
{
	/**
	 * Stores cached connections for dynamic multi-host connections.
	 *
	 * @var array<string, object> $cache
	 */
	private static array $cache = [];

	/**
	 * Stores a connection in the cache.
	 *
	 * @param string $connection The connection name.
	 * @param object $db The database connection object.
	 * @return void
	 */
	public static function set(string $connection, object $db): void
	{
		self::$cache[$connection] = $db;
	}

	/**
	 * Checks if a connection exists in the cache.
	 *
	 * @param string $connection The connection name.
	 * @return bool True if the connection exists, otherwise false.
	 */
	public static function has(string $connection): bool
	{
		return array_key_exists($connection, self::$cache);
	}

	/**
	 * Retrieves a connection from the cache.
	 *
	 * @param string $connection The connection name.
	 * @return object|null The database connection object or null if not found.
	 */
	public static function get(string $connection): ?object
	{
		return self::$cache[$connection] ?? null;
	}

	/**
	 * Removes a specific connection from the cache.
	 *
	 * @param string $connection The connection name.
	 * @return void
	 */
	public static function remove(string $connection): void
	{
		unset(self::$cache[$connection]);
	}

	/**
	 * Clears all stored connections from the cache.
	 *
	 * @return void
	 */
	public static function clear(): void
	{
		self::$cache = [];
	}
}