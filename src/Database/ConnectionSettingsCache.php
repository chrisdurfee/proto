<?php declare(strict_types=1);
namespace Proto\Database;

/**
 * ConnectionSettingsCache Class
 *
 * Manages the cache of database connections for dynamic multi-host
 * connections to ensure the same host is used for each subsequent
 * connection.
 *
 * @package Proto\Database
 */
final class ConnectionSettingsCache
{
	/**
	 * @var array<string, object> $cache The cache of database connections
	 */
	private static array $cache = [];

	/**
	 * Adds a connection to the connection cache.
	 *
	 * @param string $connection The connection name
	 * @param object $settings The connection settings
	 * @return void
	 */
	public static function set(string $connection, object $settings): void
	{
		self::$cache[$connection] = $settings;
	}

	/**
	 * Checks if a connection is present in the cache.
	 *
	 * @param string $connection The connection name
	 * @return bool Whether the connection is in the cache
	 */
	public static function has(string $connection): bool
	{
		return isset(self::$cache[$connection]);
	}

	/**
	 * Retrieves a connection from the cache.
	 *
	 * @param string $connection The connection name
	 * @return object|null The connection settings or null if not found
	 */
	public static function get(string $connection): ?object
	{
		return self::$cache[$connection] ?? null;
	}

	/**
	 * Clears a specific connection from the cache.
	 *
	 * @param string $connection The connection name
	 * @return void
	 */
	public static function clear(string $connection): void
	{
		unset(self::$cache[$connection]);
	}

	/**
	 * Clears all cached connections.
	 *
	 * @return void
	 */
	public static function clearAll(): void
	{
		self::$cache = [];
	}
}