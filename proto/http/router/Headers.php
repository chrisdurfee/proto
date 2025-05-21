<?php declare(strict_types=1);
namespace Proto\Http\Router;

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
	 * This will convert the methods array to a string.
	 *
	 * @param array $methods
	 * @return string
	 */
	protected static function convertMethodsToString(array $methods): string
	{
		return implode(', ', $methods);
	}

	/**
	 * Sets up response headers.
	 *
     * @param array<string> $methods Allowed HTTP methods.
	 * @return void
	 */
	public static function set(array $methods): void
	{
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Headers: *');
		header('Access-Control-Allow-Methods: ' . self::convertMethodsToString($methods));
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	}
}