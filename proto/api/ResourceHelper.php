<?php declare(strict_types=1);
namespace Proto\Api;

/**
 * Class ResourceHelper
 *
 * Provides helper methods for managing API resource paths.
 *
 * @package Proto\Api
 */
class ResourceHelper
{
	/**
	 * Constructs the full resource file path.
	 *
	 * @param string $resourcePath The sanitized resource path segment.
	 * @return string The complete file path to the resource.
	 */
	protected static function getResourcePath(string $resourcePath): string
	{
		return realpath(__DIR__ . '/../../modules/' . $resourcePath . '/api.php');
	}

	/**
	 * Retrieves the resource file path if it exists.
	 *
	 * @param string $url The URL representing the resource.
	 * @return string|null The file path if found, or null otherwise.
	 */
	public static function getResource(string $url): ?string
	{
		$filteredResource = self::filterResourcePath($url);
		if ($filteredResource === false)
		{
			return null;
		}

		$resourcePath = self::getResourcePath($filteredResource);
		return file_exists($resourcePath) ? $resourcePath : null;
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
	 * Filters and sanitizes the resource path to prevent directory traversal.
	 *
	 * @param string $resourcePath The raw resource path.
	 * @return string|bool The sanitized resource path, or false if invalid.
	 */
	protected static function filterResourcePath(string $resourcePath): string|bool
	{
		// Prevent directory traversal by removing dot characters.
		$resourcePath = str_replace('.', '', $resourcePath);

		// Remove any query string.
		$resourcePath = explode('?', $resourcePath)[0];

		// Remove any URL hash fragment.
		$resourcePath = explode('#', $resourcePath)[0];

		// Remove trailing slash.
		$resourcePath = preg_replace('/\/$/', '', $resourcePath);

		$parts = explode('/', $resourcePath);

		// remove numerical segments
		$parts = array_filter($parts, function ($part)
		{
			return !is_numeric($part);
		});

		$moduleName = array_shift($parts);

		// Ensure the module name is present.
		if (empty($moduleName))
		{
			return false;
		}

		/**
		 * This will place the module name at the beginning of the path
		 * and set the rest of the path to the api directory.
		 */
		return $moduleName . '/api/' . implode('/', $parts);
	}
}