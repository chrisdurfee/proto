<?php declare(strict_types=1);

namespace Proto\Http;

use Proto\Utils\Filter\Validate;

/**
 * Class PublicIp
 *
 * Handles retrieval and validation of public IP addresses.
 *
 * Only trusts proxy headers (X-Forwarded-For, X-Real-IP, etc.) when
 * REMOTE_ADDR matches a configured trusted proxy. Configure trusted
 * proxies in common/Config/.env under "trustedProxies" as an array
 * of IP addresses or CIDR ranges.
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
	 * Cached trusted proxy list.
	 *
	 * @var array|null
	 */
	protected static ?array $trustedProxies = null;

	/**
	 * Proxy headers in priority order.
	 *
	 * @var array<string>
	 */
	protected const PROXY_HEADERS = [
		'HTTP_CF_CONNECTING_IP',
		'HTTP_X_REAL_IP',
		'HTTP_X_CLUSTER_CLIENT_IP',
		'HTTP_X_FORWARDED_FOR',
		'HTTP_CLIENT_IP',
	];

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
	 * Only consults proxy headers when REMOTE_ADDR is a trusted proxy.
	 *
	 * @return string|null Public IP address or null if not found.
	 */
	protected static function fetchPublicIp(): ?string
	{
		$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;

		if (static::isTrustedProxy($remoteAddr))
		{
			foreach (self::PROXY_HEADERS as $header)
			{
				$value = $_SERVER[$header] ?? null;
				if (empty($value))
				{
					continue;
				}

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
		}

		// Fall back to REMOTE_ADDR
		if ($remoteAddr !== null && static::isValidIp($remoteAddr))
		{
			return $remoteAddr;
		}

		return null;
	}

	/**
	 * Checks if the given IP is in the trusted proxy list.
	 *
	 * When no trusted proxies are configured, proxy headers are not
	 * trusted and only REMOTE_ADDR is used.
	 *
	 * @param string|null $ip The IP to check.
	 * @return bool
	 */
	protected static function isTrustedProxy(?string $ip): bool
	{
		if ($ip === null)
		{
			return false;
		}

		$proxies = static::getTrustedProxies();
		if (empty($proxies))
		{
			return false;
		}

		foreach ($proxies as $proxy)
		{
			if ($proxy === $ip)
			{
				return true;
			}

			// Support CIDR notation
			if (str_contains($proxy, '/') && static::ipInCidr($ip, $proxy))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Retrieves the trusted proxy list from configuration.
	 *
	 * @return array<string>
	 */
	protected static function getTrustedProxies(): array
	{
		if (static::$trustedProxies !== null)
		{
			return static::$trustedProxies;
		}

		$proxies = [];
		if (function_exists('env'))
		{
			$config = env('trustedProxies');
			if (is_array($config))
			{
				$proxies = $config;
			}
		}

		return (static::$trustedProxies = $proxies);
	}

	/**
	 * Checks if an IP address falls within a CIDR range.
	 *
	 * @param string $ip The IP address.
	 * @param string $cidr The CIDR range (e.g. "10.0.0.0/8").
	 * @return bool
	 */
	protected static function ipInCidr(string $ip, string $cidr): bool
	{
		[$subnet, $bits] = explode('/', $cidr, 2);
		$bits = (int) $bits;

		$ipLong = ip2long($ip);
		$subnetLong = ip2long($subnet);

		if ($ipLong === false || $subnetLong === false)
		{
			return false;
		}

		$mask = -1 << (32 - $bits);
		return ($ipLong & $mask) === ($subnetLong & $mask);
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

	/**
	 * Resets cached state (useful for testing and long-running processes).
	 *
	 * @return void
	 */
	public static function reset(): void
	{
		static::$ipAddress = null;
		static::$trustedProxies = null;
	}
}