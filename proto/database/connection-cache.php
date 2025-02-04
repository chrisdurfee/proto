<?php declare(strict_types=1);
namespace Proto\Database;

/**
 * ConnectionCache
 *
 * This will store the connections to cache them
 * to prevent multiple connections to the same
 * database.
 *
 * @package Proto\Database
 */
final class ConnectionCache
{
    /**
     * This will store the connection cache for dynamic
     * multi-host connections.
     *
     * @var array $cache
     */
    private static array $cache = [];

    /**
     * This will set a connection to the connection cache.
     *
     * @param string $connection
     * @param object $db
     * @return void
     */
    public static function set(string $connection, object $db): void
    {
        self::$cache[$connection] = $db;
    }

    /**
     * This will check if a connection is set.
     *
     * @param string $connection
     * @return bool
     */
    public static function has(string $connection): bool
    {
        return (!empty(self::get($connection)));
    }

    /**
     * This will get a connection.
     *
     * @param string $connection
     * @return object|null
     */
    public static function get(string $connection): ?object
    {
        return self::$cache[$connection] ?? null;
    }
}