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
 * IMPORTANT: `X-Forwarded-For` (and any other proxy header) is
 * resolved using the standard trusted-proxy algorithm — the list is
 * walked right-to-left, skipping entries that match a configured
 * trusted proxy, and the first non-trusted entry found is used as
 * the client IP. This is NOT "the first IP in the header": a
 * standards-compliant reverse proxy *appends* the client IP to the
 * header rather than replacing it, so blindly trusting the leftmost
 * entry lets a client spoof its own identity (e.g.
 * `X-Forwarded-For: 1.2.3.4, <real client ip>`) and defeat IP-based
 * rate limiting. `trustedProxies` MUST list every proxy hop between
 * the internet and this server (e.g. your load balancer's IP/CIDR)
 * for this to correctly identify the real client — see
 * {@see resolveTrustedClientIp()} for the exact algorithm.
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
	 * Cached `$_SERVER` keys of the proxy headers to trust.
	 *
	 * @var array<int, string>|null
	 */
	protected static ?array $proxyHeaders = null;

	/**
	 * Proxy headers trusted by default.
	 *
	 * Only `X-Forwarded-For`: compliant proxies append to it, so the
	 * right-to-left walk recovers the real client even through several
	 * hops (e.g. Cloudflare → edge → web, with both ranges listed in
	 * `trustedProxies`). Headers a proxy passes through untouched
	 * (`CF-Connecting-IP`, `X-Real-IP`, `Client-IP`) can be set by the
	 * client whenever a request reaches a trusted hop without going
	 * through the proxy that would overwrite them, so they are only
	 * read when listed in `trustedProxyHeaders`.
	 *
	 * @var array<int, string>
	 */
	protected const DEFAULT_PROXY_HEADERS = ['X-Forwarded-For'];

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
	 * Only consults proxy headers when REMOTE_ADDR is a trusted proxy
	 * (i.e. the request arrived directly from a proxy we control /
	 * trust the topology of). Every header — including single-value
	 * ones like X-Real-IP / X-Client-IP — is resolved through
	 * {@see resolveTrustedClientIp()}, which applies the standard
	 * right-to-left trusted-proxy algorithm rather than trusting the
	 * value (or the first entry of a list) verbatim. This prevents a
	 * client from spoofing its identity by prepending a fake IP to a
	 * comma-separated header, e.g. `X-Forwarded-For: 1.2.3.4, <real>`.
	 *
	 * @return string|null Public IP address or null if not found.
	 */
	protected static function fetchPublicIp(): ?string
	{
		$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;

		if (static::isTrustedProxy($remoteAddr))
		{
			foreach (static::getProxyHeaders() as $header)
			{
				$value = $_SERVER[$header] ?? null;
				if (empty($value))
				{
					continue;
				}

				$ip = static::resolveTrustedClientIp($value);
				if ($ip !== null)
				{
					return $ip;
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
	 * Resolves the real client IP from a proxy header value using the
	 * standard trusted-proxy algorithm.
	 *
	 * A header value may be a single IP (`X-Real-IP`, `X-Client-IP`)
	 * or a comma-separated list (`X-Forwarded-For`), where compliant
	 * reverse proxies *append* the address they saw the request come
	 * from rather than replacing the header. That means the entry
	 * closest to this server — and therefore the most trustworthy one
	 * — is the *rightmost* one, not the leftmost/first one.
	 *
	 * This walks the list right-to-left, skipping any entry that
	 * itself matches a configured trusted-proxy IP/CIDR, and returns
	 * the first (i.e. rightmost) entry that is NOT a trusted proxy.
	 * That is the last hop that isn't part of our own trusted
	 * infrastructure, i.e. the real client. A naive "take the first
	 * (leftmost) entry" implementation lets a client defeat IP-based
	 * rate limiting by prepending an arbitrary spoofed IP to the
	 * header on every request.
	 *
	 * If every entry resolves to a trusted proxy (this should not
	 * normally happen — it implies there is no untrusted client hop
	 * in the chain at all), this fails safe by logging a warning and
	 * falling back to the leftmost/first entry, rather than silently
	 * trusting whichever garbage produced that state.
	 *
	 * @param string $value Raw header value.
	 * @return string|null The resolved client IP, or null when the
	 *                      header contains no valid IP addresses.
	 */
	protected static function resolveTrustedClientIp(string $value): ?string
	{
		$ips = [];
		foreach (explode(',', $value) as $candidate)
		{
			$candidate = trim($candidate);
			if (static::isValidIp($candidate))
			{
				$ips[] = $candidate;
			}
		}

		if (empty($ips))
		{
			return null;
		}

		for ($i = count($ips) - 1; $i >= 0; $i--)
		{
			if (!static::isTrustedProxy($ips[$i]))
			{
				return $ips[$i];
			}
		}

		error_log(
			'[Proto\\PublicIp] Every address in a proxy header matched a trusted proxy ' .
			'(no untrusted client hop found); falling back to the first entry: ' . $value
		);

		return $ips[0];
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

		$ip = static::unmapIpv4($ip);
		$packed = @inet_pton($ip);
		foreach ($proxies as $proxy)
		{
			if (!is_string($proxy))
			{
				continue;
			}

			if ($proxy === $ip || ($packed !== false && !str_contains($proxy, '/') && @inet_pton($proxy) === $packed))
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
	 * Retrieves the `$_SERVER` keys of the proxy headers to trust, in
	 * priority order, from `trustedProxyHeaders` (header names such as
	 * "X-Forwarded-For" or "CF-Connecting-IP").
	 *
	 * @return array<int, string>
	 */
	protected static function getProxyHeaders(): array
	{
		if (static::$proxyHeaders !== null)
		{
			return static::$proxyHeaders;
		}

		$names = self::DEFAULT_PROXY_HEADERS;
		if (function_exists('env'))
		{
			$config = env('trustedProxyHeaders');
			if (is_array($config) && $config !== [])
			{
				$names = $config;
			}
		}

		$keys = [];
		foreach ($names as $name)
		{
			if (is_string($name) && preg_match('/^[A-Za-z0-9-]+$/', $name))
			{
				$keys[] = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
			}
		}

		return (static::$proxyHeaders = $keys);
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
		[$subnet, $bitsText] = explode('/', $cidr, 2);

		// A typo ("10.0.0.0/", "10.0.0.0/abc") used to read as /0 and
		// trust every address. Malformed prefixes match nothing.
		if (!preg_match('/^\d{1,3}$/', $bitsText))
		{
			return false;
		}

		$bits = (int) $bitsText;
		$ip = static::unmapIpv4($ip);
		$subnet = static::unmapIpv4($subnet);

		// inet_pton handles IPv4 and IPv6 (Cloudflare and most clouds
		// publish IPv6 ranges; ip2long silently never matched them).
		$ipBin = @inet_pton($ip);
		$subnetBin = @inet_pton($subnet);
		if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin))
		{
			return false;
		}

		$maxBits = strlen($ipBin) * 8;
		if ($bits < 0 || $bits > $maxBits)
		{
			return false;
		}

		$fullBytes = intdiv($bits, 8);
		if (substr($ipBin, 0, $fullBytes) !== substr($subnetBin, 0, $fullBytes))
		{
			return false;
		}

		$remaining = $bits % 8;
		if ($remaining === 0)
		{
			return true;
		}

		$mask = (0xFF << (8 - $remaining)) & 0xFF;
		return (ord($ipBin[$fullBytes]) & $mask) === (ord($subnetBin[$fullBytes]) & $mask);
	}

	/**
	 * Turns an IPv4-mapped IPv6 address (`::ffff:10.0.0.5`, as dual-stack
	 * sockets report IPv4 peers) into plain IPv4 so IPv4 rules match it.
	 *
	 * @param string $ip
	 * @return string
	 */
	protected static function unmapIpv4(string $ip): string
	{
		if (preg_match('/^::ffff:(\d{1,3}(?:\.\d{1,3}){3})$/i', $ip, $match))
		{
			return $match[1];
		}

		return $ip;
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
		static::$proxyHeaders = null;
	}
}