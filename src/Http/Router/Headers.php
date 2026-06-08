<?php declare(strict_types=1);
namespace Proto\Http\Router;

use Proto\Utils\Filter\Input;

/**
 * Headers
 *
 * Handles HTTP headers including CORS with origin whitelisting.
 *
 * Configure allowed origins in common/Config/.env under "cors.allowedOrigins"
 * as an array of domain strings (e.g. ["https://example.com", "https://app.example.com"]).
 * When not configured, falls back to reflecting the request origin (development mode).
 *
 * @package Proto\Http\Router
 */
class Headers
{
	/**
	 * Default headers definition.
	 *
	 * @var array<string,string|null>
	 */
	protected static array $defaultHeaders =
	[
		'Access-Control-Allow-Origin' => null, // Will be set dynamically based on request origin
		'Access-Control-Allow-Credentials' => 'true',
		'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Cache-Control, csrf-token',
		'Access-Control-Allow-Methods' => null, // placeholder
		'Access-Control-Max-Age' => '86400',
		'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
		'X-Content-Type-Options' => 'nosniff',
		'X-Frame-Options' => 'DENY',
		'Referrer-Policy' => 'strict-origin-when-cross-origin'
	];

	/**
	 * Cached allowed origins from configuration.
	 *
	 * @var array|null
	 */
	protected static ?array $allowedOrigins = null;

	/**
	 * Convert the methods array to a comma-separated string.
	 *
	 * @param array<string> $methods
	 * @return string
	 */
	protected static function convertMethodsToString(array $methods): string
	{
		return implode(', ', $methods);
	}

	/**
	 * Retrieves allowed origins from configuration.
	 *
	 * @return array<string>
	 */
	protected static function getAllowedOrigins(): array
	{
		if (static::$allowedOrigins !== null)
		{
			return static::$allowedOrigins;
		}

		$origins = [];
		if (function_exists('env'))
		{
			$cors = env('cors');
			if (is_object($cors) && isset($cors->allowedOrigins) && is_array($cors->allowedOrigins))
			{
				$origins = $cors->allowedOrigins;
			}
		}

		return (static::$allowedOrigins = $origins);
	}

	/**
	 * Validates whether the given origin is allowed.
	 *
	 * When no allowed origins are configured, all origins are permitted
	 * (development fallback). In production, configure allowedOrigins.
	 *
	 * @param string $origin The request origin.
	 * @return bool
	 */
	protected static function isAllowedOrigin(string $origin): bool
	{
		$allowed = static::getAllowedOrigins();
		if (empty($allowed))
		{
			return true;
		}

		return in_array($origin, $allowed, true);
	}

	/**
	 * Prepare the headers array for a given set of allowed methods.
	 *
	 * @param array<string> $methods
	 * @return array<string,string>
	 */
	protected static function prepare(array $methods): array
	{
		$headers = self::$defaultHeaders;
		$headers['Access-Control-Allow-Methods'] = self::convertMethodsToString($methods);

		// Set origin from request (required for credentials).
		// Strip CR/LF to prevent HTTP header injection via the Origin header.
		$origin = str_replace(["\r", "\n", "\0"], '', Input::server('HTTP_ORIGIN'));
		if ($origin !== '' && static::isAllowedOrigin($origin))
		{
			$headers['Access-Control-Allow-Origin'] = $origin;
		}
		else
		{
			// No valid origin — omit CORS credentials headers
			unset($headers['Access-Control-Allow-Origin']);
			unset($headers['Access-Control-Allow-Credentials']);
		}

		// Enforce HTTPS via HSTS in production over secure connections only.
		if (static::isSecureProduction())
		{
			$headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
		}

		return $headers;
	}

	/**
	 * Determines whether the current request is a secure (HTTPS) production request.
	 *
	 * HSTS must only be emitted over HTTPS and is scoped to production to avoid
	 * pinning local/staging hosts during development.
	 *
	 * @return bool
	 */
	protected static function isSecureProduction(): bool
	{
		if (function_exists('env') && env('env') !== 'prod')
		{
			return false;
		}

		$https = Input::server('HTTPS');
		if (!empty($https) && strtolower((string)$https) !== 'off')
		{
			return true;
		}

		// Honor a trusted forwarded protocol header (terminating proxy/load balancer).
		return strtolower((string)Input::server('HTTP_X_FORWARDED_PROTO')) === 'https';
	}

	/**
	 * Render (send) all headers in the given array.
	 *
	 * @param array<string,string> $headers
	 * @return void
	 */
	public static function render(array $headers): void
	{
		foreach ($headers as $name => $value)
		{
			header("{$name}: {$value}");
		}
	}

	/**
	 * Public entry point: set up and send all standard headers.
	 *
	 * @param array<string> $methods Allowed HTTP methods.
	 * @return void
	 */
	public static function set(array $methods): void
	{
		$headers = self::prepare($methods);
		self::render($headers);
	}
}
