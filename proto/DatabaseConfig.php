<?php declare(strict_types=1);
namespace Proto;

use Proto\Database\ConnectionSettingsCache;

/**
 * DatabaseConfig Class
 *
 * Manages database configuration settings.
 *
 * @package Proto
 */
class DatabaseConfig
{
	/**
	 * Retrieves database connection settings.
	 *
	 * @param string|null $connection
	 * @return object|null
	 */
	public static function getConnectionSettings(?string $connection = 'default'): ?object
	{
		$config = Config::getInstance();
		$settings = $config->get('connections')->{$connection} ?? null;
		if ($settings === null)
        {
			return null;
		}

		return self::resolveEnvironmentSettings($settings);
	}

	/**
	 * Retrieves the correct environment-specific database settings.
	 *
	 * @param object $settings
	 * @return object
	 */
	private static function resolveEnvironmentSettings(object $settings): object
	{
		$config = Config::getInstance();
		$env = $config->getEnv();

		return $settings->{$env} ?? $settings->prod ?? $settings;
	}

	/**
	 * Handles multi-host database configurations.
	 *
	 * @param string $connection
	 * @param object $settings
     * @param string $env
	 * @return object
	 */
	private static function handleMultiHost(string $connection, object $settings, string $env): object
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

	/**
	 * Retrieves database settings for a specific connection.
	 *
	 * @param string|null $connection
     * @param string|null $env
	 * @return object
	 */
	public static function getDBSettings(
        ?string $connection = 'default',
        ?string $env = 'dev'
    ): object
	{
		$settings = self::getConnectionSettings($connection);
		if ($settings === null)
        {
			throw new \RuntimeException('No connection settings are configured.');
		}

		return self::handleMultiHost($connection, (object) $settings, $env);
	}
}