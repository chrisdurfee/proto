<?php declare(strict_types=1);
namespace Proto\Http\Router;

/**
 * UriQuery
 *
 * This will compile the URI query into a regex for matching.
 *
 * Every pattern is anchored at both ends. Supported segments:
 *
 *  - `literal`        matched exactly
 *  - `:name`          one path segment
 *  - `:name?`         an optional segment (the slash before it is optional too)
 *  - `:name.json`     one segment that must end in `.json` (the value keeps the suffix)
 *  - `*` (anywhere)   any characters, including further segments (`files/*`, `migration*`)
 *
 * Earlier versions dropped the end anchor whenever the compiled pattern
 * contained `*`, which optional parameters followed by more segments
 * (`/:id?/edit`) and dotted parameters (`/:name.json`) also produced.
 * Those routes then matched any longer URL with the same prefix, sending
 * requests to the wrong controller and policy.
 *
 * @package Proto\Http\Router
 * @abstract
 */
abstract class UriQuery
{
	/**
	 * Characters a parameter value may contain (one path segment).
	 */
	protected const SEGMENT = '[^\/|?]+';

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

		// Request paths are matched without a trailing slash.
		$trimmed = rtrim($uri, '/');
		if ($trimmed === '')
		{
			return '/^\/?$/';
		}

		$leadingSlash = str_starts_with($trimmed, '/');
		$segments = explode('/', $leadingSlash ? substr($trimmed, 1) : $trimmed);

		$pattern = '';
		foreach ($segments as $index => $segment)
		{
			$separator = ($index > 0 || $leadingSlash) ? '\/' : '';
			$pattern .= static::compileSegment($segment, $separator);
		}

		return '/^' . $pattern . '$/';
	}

	/**
	 * Compiles one path segment, including the slash that precedes it.
	 *
	 * @param string $segment Raw segment text.
	 * @param string $separator Escaped slash, or '' for a relative first segment.
	 * @return string
	 */
	protected static function compileSegment(string $segment, string $separator): string
	{
		if ($segment === '*')
		{
			return $separator . '.*';
		}

		// Optional parameter: ":id?" (the separator is optional with it)
		if (preg_match('/^:(\w+)\?$/', $segment))
		{
			return '(?:' . $separator . '(' . self::SEGMENT . '))?';
		}

		// Parameter, optionally followed by literal text: ":id", ":name.json"
		if (preg_match('/^:(\w+)(.*)$/', $segment, $match))
		{
			$suffix = $match[2];
			if ($suffix === '')
			{
				return $separator . '(' . self::SEGMENT . ')';
			}

			return $separator . '(' . '[^\/|?]*?' . static::compileLiteral($suffix) . ')';
		}

		return $separator . static::compileLiteral($segment);
	}

	/**
	 * Escapes literal text, keeping `*` as a wildcard.
	 *
	 * @param string $text
	 * @return string
	 */
	protected static function compileLiteral(string $text): string
	{
		$parts = explode('*', $text);
		$escaped = array_map(fn(string $part): string => preg_quote($part, '/'), $parts);
		return implode('.*', $escaped);
	}
}
