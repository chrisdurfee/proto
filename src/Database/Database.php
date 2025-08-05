<?php declare(strict_types=1);
namespace Proto\Database;

use Proto\Database\Adapters\Mysqli;
use Proto\Config;

/**
 * Database
 *
 * This class creates a connection to the database using
 * an adapter that is registered in the .env file.
 *
 * @package Proto\Database
 */
class Database
{
	/**
	 * Determines if connection caching is enabled.
	 *
	 * @param bool $caching Force-enable caching if true.
	 * @return bool True if caching is enabled, otherwise false.
	 */
	protected function isCaching(bool $caching = false): bool
	{
		return $caching || (bool) env('dbCaching');
	}

	/**
	 * Connects to the database.
	 *
	 * @param string $connection Connection name from the config file.
	 * @param bool $caching Whether to use connection caching or not.
	 * @return Mysqli|null Returns Mysqli instance or null if settings are missing.
	 */
	public function connect(string $connection = 'default', bool $caching = false): ?Mysqli
	{
		$settings = $this->getConnectionSettings($connection);
		if (!$settings)
		{
			return null;
		}

		$caching = $this->isCaching($caching);
		if ($caching)
		{
			$cachedConnection = ConnectionSettingsCache::get($connection);
			if ($cachedConnection instanceof Mysqli)
			{
				return $cachedConnection;
			}
		}

		$db = $this->createAdapter($settings, $caching);

		if ($caching)
		{
			ConnectionSettingsCache::set($connection, $db);
		}

		return $db;
	}

	/**
	 * Creates a database adapter based on the config settings.
	 *
	 * @param object $settings Connection settings.
	 * @param bool $caching Whether to use connection caching.
	 * @return Mysqli Returns Mysqli instance.
	 * @throws \RuntimeException If an unsupported adapter is found in the config.
	 */
	protected function createAdapter(object $settings, bool $caching): Mysqli
	{
		if (env('database') !== 'Mysqli')
		{
			throw new \RuntimeException('Only Mysqli is supported.');
		}

		return new Mysqli($settings, $caching);
	}

	/**
	 * Retrieves the connection settings from the config.
	 *
	 * @param string $connection Connection name.
	 * @return object Connection settings object.
	 */
	protected function getConnectionSettings(string $connection): object
	{
		return Config::getInstance()->getDBSettings($connection);
	}

	/**
	 * Retrieves a database connection.
	 *
	 * @param string $connection Connection name.
	 * @param bool $caching Whether to use connection caching.
	 * @return Mysqli|null Returns Mysqli instance or null if settings are missing.
	 */
	public static function getConnection(string $connection = 'default', bool $caching = false): ?Mysqli
	{
		return (new self())->connect($connection, $caching);
	}
}