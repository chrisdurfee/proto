<?php
namespace Proto;

/**
 * ConnectionCache
 *
 * This will store the connection cache for dynamic multi-host
 * connections so the config class reuses the same host for
 * each subsequent connection.
 */
final class ConnectionCache
{
    /**
     * This will store the connection cache for dynamic
     * multi-host connections.
     *
     * @var array
     */
    private static $cache = [

    ];

    /**
     * This will set a connection to the connection cache.
     *
     * @param string $connection
     * @param object $settings
     * @return void
     */
    public static function set(string $connection, object $settings): void
    {
        self::$cache[$connection] = $settings;
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