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

		$uriQuery = '/^';
		// escape slashes
		$uriQuery .= preg_replace('/\//', '\/', $uri);

		// add slash before optional param
		$uriQuery = preg_replace('/(\\\*\/)(:[^\/(]*?\?)/', '(?:$|\/)$2', $uriQuery);

		// add slash after optional param
		$uriQuery = preg_replace('/(\?\\\\\/+\*?)/', '?\/*', $uriQuery);

		// params
		$paramCallBack = function($matches)
		{
			if (strpos($matches[0], '.') === false)
			{
				return '([^\/|?]+)';
			}

			return '([^\/|?]+.*)';
		};

		$uriQuery = preg_replace_callback('/(:[^\/?&$\\\]+)/', $paramCallBack, $uriQuery);

		// wild card to allow all
		$uriQuery = preg_replace('/(?<!\.)(\*)/', '.*', $uriQuery);

		// this will only add the end string char if the uri has no wild cards
		$uriQuery .= (strpos($uriQuery, '*') === false)? '$/' : '/';
		return $uriQuery;
	}
}