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
	 * `Cache-Control` and `Vary` are omitted here because they depend on the
	 * active cache directive (@see directive()).
	 *
	 * @var array<string,string|null>
	 */
	protected static array $defaultHeaders =
	[
		'Access-Control-Allow-Origin' => null, // Will be set dynamically based on request origin
		'Access-Control-Allow-Credentials' => 'true',
		'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Cache-Control, If-None-Match, csrf-token',
		'Access-Control-Allow-Methods' => null, // placeholder
		'Access-Control-Expose-Headers' => 'ETag',
		'Access-Control-Max-Age' => '86400',
		'X-Content-Type-Options' => 'nosniff',
		'X-Frame-Options' => 'DENY',
		'Referrer-Policy' => 'strict-origin-when-cross-origin'
	];

	/**
	 * Cache dimensions every response varies on.
	 *
	 * `Access-Control-Allow-Origin` is reflected from the request, so a
	 * cache that ignored `Origin` could hand a response bearing one site's
	 * origin to another. `Accept-Encoding` keeps a compressed body from
	 * being served to a client that cannot decode it.
	 *
	 * @var array<int, string>
	 */
	protected const BASE_VARY = ['Origin', 'Accept-Encoding'];

	/**
	 * Cached allowed origins from configuration.
	 *
	 * @var array|null
	 */
	protected static ?array $allowedOrigins = null;

	/**
	 * Cache directive for the current request, or null to use the default.
	 *
	 * Reset by set() at the start of every request so a directive chosen by
	 * one request cannot leak into the next on a long-lived FPM worker.
	 *
	 * @var CacheDirective|null
	 */
	protected static ?CacheDirective $directive = null;

	/**
	 * Whether conditional requests are enabled, or null when unresolved.
	 *
	 * @var bool|null
	 */
	protected static ?bool $conditionalRequests = null;

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
	 * Whether the app allows conditional requests (ETag/304) and a
	 * revalidate-friendly `Cache-Control`.
	 *
	 * Enabled by default. Set `router.conditionalRequests` to false to fall
	 * back to `no-store` on every response, which forbids the client from
	 * keeping a copy and therefore disables 304 revalidation entirely.
	 *
	 * @return bool
	 */
	public static function conditionalRequestsEnabled(): bool
	{
		if (static::$conditionalRequests !== null)
		{
			return static::$conditionalRequests;
		}

		$enabled = true;
		if (function_exists('env'))
		{
			$router = env('router');
			if (is_object($router) && isset($router->conditionalRequests))
			{
				$enabled = (bool)$router->conditionalRequests;
			}
		}

		return (static::$conditionalRequests = $enabled);
	}

	/**
	 * Sets the cache directive for the current response.
	 *
	 * @param CacheDirective $directive
	 * @return void
	 */
	public static function cache(CacheDirective $directive): void
	{
		static::$directive = $directive;
	}

	/**
	 * The cache directive for the current response.
	 *
	 * @return CacheDirective
	 */
	public static function directive(): CacheDirective
	{
		if (static::$directive !== null)
		{
			return static::$directive;
		}

		return static::conditionalRequestsEnabled()
			? CacheDirective::revalidate()
			: CacheDirective::noStore();
	}

	/**
	 * Builds the `Vary` value for a directive.
	 *
	 * A response that may be reused without revalidation also varies on the
	 * session cookie, otherwise a device that switched users could reuse the
	 * previous user's copy for the remainder of its freshness window.
	 *
	 * @param CacheDirective $directive
	 * @return string
	 */
	protected static function buildVary(CacheDirective $directive): string
	{
		$vary = self::BASE_VARY;
		if ($directive->isReusable())
		{
			$vary[] = 'Cookie';
		}

		return implode(', ', $vary);
	}

	/**
	 * Sends the cache headers for the active directive.
	 *
	 * Called again by the response layer once the body is known, so a
	 * controller that chose a directive mid-request still wins over the
	 * default sent while the router was booting.
	 *
	 * @return void
	 */
	public static function sendCacheHeaders(): void
	{
		if (headers_sent())
		{
			return;
		}

		$directive = static::directive();
		header('Cache-Control: ' . $directive->value());
		header('Vary: ' . self::buildVary($directive));
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

		$directive = static::directive();
		$headers['Cache-Control'] = $directive->value();
		$headers['Vary'] = self::buildVary($directive);

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
	 * Clears any directive chosen by a previous request first, so a
	 * long-lived FPM worker starts each request from the default.
	 *
	 * @param array<string> $methods Allowed HTTP methods.
	 * @return void
	 */
	public static function set(array $methods): void
	{
		static::$directive = null;

		$headers = self::prepare($methods);
		self::render($headers);
	}

	/**
	 * Clears memoized state between requests and tests.
	 *
	 * @return void
	 */
	public static function reset(): void
	{
		static::$directive = null;
		static::$allowedOrigins = null;
		static::$conditionalRequests = null;
	}
}
