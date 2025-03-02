<?php declare(strict_types=1);
namespace Proto\Database;

use Proto\Database\ConnectionSettingsCache;

/**
 * DatabaseManager Class
 *
 * Manages database configuration and connection settings.
 *
 * @package Proto\Database
 */
class DatabaseManager
{
	/**
	 * Retrieves database connection settings.
	 *
     * @param object $connections
	 * @param string|null $connection
     * @param string|null $env
	 * @return object
	 * @throws \Exception
	 */
	public static function getDBSettings(
        object $connections,
        ?string $connection = 'default',
        ?string $env = 'dev'): object
	{
		$settings = self::getConnectionSettings($connections, $connection, $env);
		if ($settings === null)
		{
			throw new \RuntimeException("No connection settings found for '{$connection}'");
		}

		return self::handleMultiHost($connection, (object) $settings);
	}

	/**
	 * Retrieves the correct connection settings for the given environment.
	 *
     * @param object $connections
	 * @param string $connection
     * @param string $env
	 * @return object|null
	 */
	private static function getConnectionSettings(object $connections, string $connection, string $env): ?object
	{
		if (!isset($connections->{$connection}))
		{
			return null;
		}

		return self::resolveEnvironmentSettings($connections->{$connection}, $env);
	}

	/**
	 * Retrieves the correct environment-specific database settings.
	 *
	 * @param object $settings
     * @param string $env
	 * @return object
	 */
	private static function resolveEnvironmentSettings(object $settings, string $env): object
	{
		return $settings->{$env} ?? $settings->prod ?? $settings;
	}

	/**
	 * Handles multi-host database configurations.
	 *
	 * @param string $connection
	 * @param object $settings
	 * @return object
	 */
	private static function handleMultiHost(string $connection, object $settings): object
	{
		if (!isset($settings->host) || !is_array($settings->host))
		{
			return $settings;
		}

		// Check cache before selecting a new host
		$cachedSettings = ConnectionSettingsCache::get($connection);
		if ($cachedSettings)
		{
			return $cachedSettings;
		}

		// Randomly select a host
		$settings->host = $settings->host[array_rand($settings->host)];

		// Cache the selection for consistency
		ConnectionSettingsCache::set($connection, $settings);

		return $settings;
	}
}