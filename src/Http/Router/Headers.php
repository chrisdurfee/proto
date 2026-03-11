<?php declare(strict_types=1);
namespace Proto\Http\Router;

use Proto\Utils\Filter\Input;

/**
 * Headers
 *
 * Handles HTTP headers.
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
		'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Cache-Control',
		'Access-Control-Allow-Methods' => null, // placeholder
		'Access-Control-Max-Age' => '86400',
		'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
		'X-Content-Type-Options' => 'nosniff',
		'X-Frame-Options' => 'DENY',
		'Referrer-Policy' => 'strict-origin-when-cross-origin'
	];

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
		if ($origin !== '')
		{
			$headers['Access-Control-Allow-Origin'] = $origin;
		}
		else
		{
			// Fallback for non-CORS requests
			unset($headers['Access-Control-Allow-Origin']);
			unset($headers['Access-Control-Allow-Credentials']);
		}

		return $headers;
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
