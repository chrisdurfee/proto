<?php declare(strict_types=1);

namespace Proto\Http;

use Proto\Utils\Filter\Validate;
use Proto\Utils\Filter\Input;

/**
 * Class PublicIp
 *
 * Handles retrieval and validation of public IP addresses.
 *
 * @package Proto\Http
 */
class PublicIp
{
	/**
	 * Cached public IP address.
	 *
	 * @var string|null
	 */
	protected static ?string $ipAddress = null;

	/**
	 * Retrieves the public IP address.
	 *
	 * Caches the result to prevent redundant lookups.
	 *
	 * @return string|null Public IP address or null if not found.
	 */
	public static function get(): ?string
	{
		return static::$ipAddress ?? (static::$ipAddress = static::fetchPublicIp());
	}

	/**
	 * Fetches the public IP address from server headers.
	 *
	 * @return string|null Public IP address or null if not found.
	 */
	protected static function fetchPublicIp(): ?string
	{
		$headers = [
			"HTTP_CLIENT_IP",
			"HTTP_X_FORWARDED_FOR",
			"REMOTE_ADDR"
		];

		foreach ($headers as $header)
		{
			if (!empty($_SERVER[$header]))
			{
				$ipList = explode(',', $_SERVER[$header]);
				$ip = trim($ipList[0]); // Take the first IP in case of multiple proxies

				if (self::isValidIp($ip))
				{
					return $ip;
				}
			}
		}

		return null;
	}

	/**
	 * Validates an IP address.
	 *
	 * @param string|null $ip IP address to validate.
	 * @return bool True if valid, false otherwise.
	 */
	protected static function isValidIp(?string $ip): bool
	{
		return Validate::ip($ip);
	}
}