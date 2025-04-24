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
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_REAL_IP',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_CLIENT_IP',
			'REMOTE_ADDR',
		];

		foreach ($headers as $header)
		{
			$value = $_SERVER[$header] ?? null;
			if (empty($value))
			{
				continue;
			}

			// X-Forwarded-For and similar can be a comma-separated list.
			$candidates = explode(',', $value);
			foreach ($candidates as $ip)
			{
				$ip = trim($ip);
				if (static::isValidIp($ip))
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
		if (empty($ip))
		{
			return false;
		}

		return Validate::ip($ip);
	}
}