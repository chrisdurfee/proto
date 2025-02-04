<?php declare(strict_types=1);
namespace Proto\Database;

use Proto\Database\Adapters\Mysqli;
use Proto\Base;

/**
 * Database
 *
 * This class creates a connection to the database using
 * an adapter that is registered in the .env file.
 *
 * @package Proto\Database
 */
class Database extends Base
{
	/**
	 * This will check if we are caching the connection.
	 *
	 * @param bool $caching
	 * @return bool
	 */
	protected function isCaching(bool $caching = false): bool
	{
		$dbCaching = env('dbCaching') ?? false;
		return ($caching === true)? true : $dbCaching;
	}

	/**
	 * Connect to the database.
	 *
	 * @param string $connection Connection name from the config file.
	 * @param bool $caching Whether to use the connection caching or not.
	 * @return Mysqli|bool Returns Mysqli instance or false if settings not found.
	 */
	public function connect(string $connection = 'proto', bool $caching = false): Mysqli|bool
	{
		$settings = $this->getConnectionSettings($connection);
		if (!isset($settings))
		{
			return false;
		}

		$caching = $this->isCaching($caching);
		if ($caching)
		{
			$cache = ConnectionCache::get($connection);
			if ($cache)
			{
				return $cache;
			}
		}

		$db = $this->getAdapter($settings, $caching);

		if ($caching)
		{
			ConnectionCache::set($connection, $db);
		}
		return $db;
	}

	/**
	 * Get the adapter based on the config settings.
	 *
	 * @param object $settings Connection settings.
	 * @param bool $caching Whether to use the connection caching or not.
	 * @return Mysqli Returns Mysqli instance.
	 * @throws \Exception Throws exception if unsupported adapter is found in the config.
	 */
	protected function getAdapter(object $settings, bool $caching): Mysqli
	{
		$config = $this->getConfig();
		if ($config->database !== 'Mysqli')
		{
			throw new \Exception('Only Mysqli is supported.');
		}

		return new Mysqli($settings, $caching);
	}

	/**
	 * Get the connection settings from the config.
	 *
	 * @param string $connection Connection name.
	 * @return object Returns an object containing connection settings.
	 */
	protected function getConnectionSettings(string $connection): object
	{
		$config = $this->getConfig();
		return $config->getDBSettings($connection);
	}

	/**
	 * Get a database connection.
	 *
	 * @param string $connection Connection name.
	 * @param bool $caching Whether to use the connection caching or not.
	 * @return Mysqli|bool Returns Mysqli instance or false if settings not found.
	 */
	public static function getConnection(string $connection = 'proto', bool $caching = false): Mysqli|bool
	{
		$db = new static();
		return $db->connect($connection);
	}
}
