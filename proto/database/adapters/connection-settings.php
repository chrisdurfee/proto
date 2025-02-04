<?php declare(strict_types=1);
namespace Proto\Database\Adapters;

/**
 * ConnectionSettings
 *
 * This class creates database adapter connection settings.
 *
 * @package Proto\Database\Adapters
 */
class ConnectionSettings
{
	/**
	 * @var string $host
	 */
	public string $host;

	/**
	 * @var string $username
	 */
	public string $username;

	/**
	 * @var string $password
	 */
	public string $password;

	/**
	 * @var string $database
	 */
	public string $database;

	/**
	 * @var int $port
	 */
	public int $port;

	/**
	 * Sets the connection settings.
	 *
	 * @param object $settings
	 * @return void
	 */
	public function __construct(object $settings)
	{
		$this->setSettings($settings);
	}

	/**
	 * Sets the connection settings.
	 *
	 * @param object $settings
	 * @return void
	 */
	public function setSettings(object $settings): void
	{
		$this->host = $settings->host ?? 'localhost';
		$this->username = $settings->username ?? '';
		$this->password = $settings->password ?? '';
		$this->database = $settings->database ?? '';
		$this->port = $settings->port ?? 3306;
	}
}