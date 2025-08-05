<?php declare(strict_types=1);
namespace Proto\Database\Adapters;

/**
 * ConnectionSettings
 *
 * Handles database adapter connection settings.
 *
 * @package Proto\Database\Adapters
 */
class ConnectionSettings
{
	/**
	 * @var string $host Database host.
	 */
	public readonly string $host;

	/**
	 * @var string $username Database username.
	 */
	public readonly string $username;

	/**
	 * @var string $password Database password.
	 */
	public readonly string $password;

	/**
	 * @var string $database Database name.
	 */
	public readonly string $database;

	/**
	 * @var int $port Database port.
	 */
	public readonly int $port;

	/**
	 * Constructor
	 *
	 * @param array|object $settings Raw connection settings.
	 */
	public function __construct(array|object $settings)
	{
		$this->host = $settings->host ?? 'localhost';
		$this->username = $settings->username ?? '';
		$this->password = $settings->password ?? '';
		$this->database = $settings->database ?? '';
		$this->port = isset($settings->port) ? (int) $settings->port : 3306;
	}
}