<?php declare(strict_types=1);
namespace Proto\Http\Router;

/**
 * UriQuery
 *
 * This will compile the URI query into a regex for matching.
 *
 * @package Proto\Http\Router
 * @abstract
 */
abstract class UriQuery
{
	/**
	 * Compiles the URI pattern into a regex for matching.
	 *
	 * @param string $uri The route URI.
	 * @return string
	 */
	public static function create(string $uri): string
	{
		if ($uri === '')
		{
			return '/.*/';
		}

		// Escape slashes
		$uriQuery = preg_replace('/\//', '\/', $uri);
		// Replace optional parameters
		$uriQuery = preg_replace('/:(\w+)\?/', '(?P<\1>[^\/]*)?', $uriQuery);
		// Replace required parameters
		$uriQuery = preg_replace('/:(\w+)/', '(?P<\1>[^\/]+)', $uriQuery);
		// Wildcard match
		$uriQuery = str_replace('*', '.*', $uriQuery);

		return '/^' . $uriQuery . '$/';
	}
}