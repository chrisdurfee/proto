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
	 * Whether to use a persistent (`p:`) connection. Defaults to true
	 * (existing behavior). Set to `false` (e.g. in tests) to force a
	 * fresh, non-persistent connection so autocommit/transaction state
	 * doesn't leak between requests/tests via a pooled connection.
	 *
	 * @var bool $persistent
	 */
	public readonly bool $persistent;

	/**
	 * Connect timeout in seconds, used via `MYSQLI_OPT_CONNECT_TIMEOUT`
	 * so an unreachable host fails fast instead of blocking for PHP's
	 * default socket timeout (~60s). Defaults to 5 seconds.
	 *
	 * @var int $connectTimeout
	 */
	public readonly int $connectTimeout;

	/**
	 * Constructor
	 *
	 * @param array|object $settings Raw connection settings.
	 */
	public function __construct(array|object $settings)
	{
		$settings = is_array($settings) ? (object) $settings : $settings;

		$this->host = $settings->host ?? 'localhost';
		$this->username = $settings->username ?? '';
		$this->password = $settings->password ?? '';
		$this->database = $settings->database ?? '';
		$this->port = isset($settings->port) ? (int) $settings->port : 3306;
		$this->persistent = isset($settings->persistent) ? (bool) $settings->persistent : true;
		$this->connectTimeout = isset($settings->connectTimeout) ? (int) $settings->connectTimeout : 5;
	}
}