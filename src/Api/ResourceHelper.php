<?php declare(strict_types=1);
namespace Proto\Api;

use Proto\Utils\Strings;

/**
 * Class ResourceHelper
 *
 * Provides helper methods for managing API resource paths.
 *
 * Supports both flat module structure and nested feature modules:
 * - Flat: /api/user → modules/User/Api/api.php
 * - Nested: /api/community/group → modules/Community/Group/Api/api.php
 * - Deep nested: /api/community/group/forum → modules/Community/Group/Forum/Api/api.php
 * - Main folder: /api/user → modules/User/Main/Api/api.php (fallback for module root)
 *
 * @package Proto\Api
 */
class ResourceHelper
{
	/**
	 * Constructs the full resource file path.
	 *
	 * @param string $resourcePath The sanitized resource path segment.
	 * @return string|null The complete file path to the resource.
	 */
	protected static function getResourcePath(string $resourcePath): ?string
	{
		$path = realpath(BASE_PATH . '/modules/' . $resourcePath . '/api.php');
		return ($path) ? $path : null;
	}

	/**
	 * Retrieves the resource file path if it exists.
	 *
	 * @param string $url The URL representing the resource.
	 * @return string|null The file path if found, or null otherwise.
	 */
	public static function getResource(string $url): ?string
	{
		$parts = self::getFilteredParts($url);
		if ($parts === false || empty($parts))
		{
			return null;
		}

		return self::resolveResourcePath($parts);
	}

	/**
	 * Resolves the resource path using nested feature module resolution.
	 *
	 * Tries all possible split points from deepest nesting to shallowest.
	 * For parts [A, B, C], resolution order per split:
	 *
	 * Split at 3: modules/A/B/C/Api/api.php, modules/A/B/C/Main/Api/api.php
	 * Split at 2: modules/A/B/Api/C/api.php, modules/A/B/Main/Api/C/api.php
	 * Split at 1: modules/A/Api/B/C/api.php, modules/A/Main/Api/B/C/api.php
	 *
	 * Then recursive fallback with fewer segments.
	 *
	 * @param array $parts The URL path segments (PascalCased).
	 * @return string|null The file path if found, or null otherwise.
	 */
	protected static function resolveResourcePath(array $parts): ?string
	{
		if (empty($parts))
		{
			return null;
		}

		$count = count($parts);

		// Try all split points from deepest nesting to shallowest.
		// Split at $i means the first $i segments form the directory path,
		// and the remaining segments go under the Api/ directory.
		for ($i = $count; $i >= 1; $i--)
		{
			$dirParts = array_slice($parts, 0, $i);
			$apiParts = array_slice($parts, $i);
			$dirPath = implode('/', $dirParts);
			$apiSubPath = !empty($apiParts) ? '/' . implode('/', $apiParts) : '';

			// Direct: modules/A/B/C/Api/.../api.php
			$result = self::getResourcePath($dirPath . '/Api' . $apiSubPath);
			if ($result)
			{
				return $result;
			}

			// Main fallback: modules/A/B/C/Main/Api/.../api.php
			$result = self::getResourcePath($dirPath . '/Main/Api' . $apiSubPath);
			if ($result)
			{
				return $result;
			}
		}

		// Recursive fallback: try with fewer path segments
		if ($count > 1)
		{
			return self::resolveResourcePath(array_slice($parts, 0, -1));
		}

		return null;
	}

	/**
	 * Retrieves the resource file path from the URL.
	 *
	 * @deprecated Use resolveResourcePath() instead. Kept for backward compatibility.
	 * @param string $url The URL representing the resource.
	 * @return string|null The file path if found, or null otherwise.
	 */
	protected static function getResourcePathFromUrl(string $url): ?string
	{
		if (empty($url))
		{
			return null;
		}

		$resourcePath = self::getResourcePath($url);
		if (empty($resourcePath))
		{
			$resourcePath = self::removeLastPart($url);
			if (empty($resourcePath))
			{
				return null;
			}

			return self::getResourcePathFromUrl($resourcePath);
		}

		return $resourcePath;
	}

	/**
	 * Includes the specified resource file.
	 *
	 * @param string $resourcePath The path of the resource file.
	 * @return void
	 */
	public static function includeResource(string $resourcePath): void
	{
		require_once $resourcePath;
	}

	/**
	 * Removes the last part of the resource path.
	 *
	 * @param string $resourcePath The resource path to be modified.
	 * @return string The resource path without the last part.
	 */
	protected static function removeLastPart(string $resourcePath): string
	{
		$DIVIDER = '/';
		$parts = explode($DIVIDER, $resourcePath);
		array_pop($parts);
		return implode($DIVIDER, $parts);
	}

	/**
	 * Filters and sanitizes the URL path, returning PascalCased segments.
	 *
	 * @param string $url The raw URL path.
	 * @return array|false Array of PascalCased path segments, or false if invalid.
	 */
	protected static function getFilteredParts(string $url): array|false
	{
		// Decode percent-encoded characters before sanitizing to block
		// traversal via encoded sequences such as %2e, %2f, %5c, or null bytes.
		$url = rawurldecode($url);

		// Remove null bytes.
		$url = str_replace("\0", '', $url);

		// Prevent directory traversal by removing dot characters.
		$url = str_replace('.', '', $url);

		// Remove any query string.
		$url = explode('?', $url)[0];

		// Remove any URL hash fragment.
		$url = explode('#', $url)[0];

		// Remove trailing slash.
		$url = preg_replace('/\/$/', '', $url);

		$parts = explode('/', $url);

		// Remove empty and numerical segments
		$parts = array_values(array_filter($parts, function($part)
		{
			return !empty($part) && !is_numeric($part);
		}));

		if (empty($parts))
		{
			return false;
		}

		// Convert all segments to PascalCase
		return array_map(fn($part) => Strings::pascalCase($part), $parts);
	}

	/**
	 * Filters and sanitizes the resource path to prevent directory traversal.
	 *
	 * @deprecated Use getFilteredParts() instead. Kept for backward compatibility.
	 * @param string $resourcePath The raw resource path.
	 * @return string|bool The sanitized resource path, or false if invalid.
	 */
	protected static function filterResourcePath(string $resourcePath): string|bool
	{
		$parts = self::getFilteredParts($resourcePath);
		if ($parts === false || empty($parts))
		{
			return false;
		}

		$moduleName = array_shift($parts);

		/**
		 * This will place the module name at the beginning of the path
		 * and set the rest of the path to the api directory.
		 */
		return $moduleName . '/Api/' . implode('/', $parts);
	}
}