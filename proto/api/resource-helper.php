<?php declare(strict_types=1);
namespace Proto\Api;

/**
 * ResourceHelper
 *
 * This will help with the resource.
 *
 * @package Proto\Api
 */
class ResourceHelper
{
	/**
	 * This will get the resource path.
	 *
	 * @param string $resourcePath
	 * @return string
	 */
	protected static function getResourcePath(string $resourcePath): string
	{
		return __DIR__ . '/../../modules/' . $resourcePath . '/api/Api.php';
	}

	/**
	 * This will get the resource.
	 *
	 * @param string $url
	 * @return mixed
	 */
	public static function getResource(string $url): mixed
	{
		$resourcePath = self::filterResourcePath($url);
        if (!$resourcePath)
		{
			return null;
		}

        $resourcePath = self::getResourcePath($resourcePath);
        return (file_exists($resourcePath)) ? $resourcePath : null;
	}

    /**
     * This will include the resource.
     *
     * @param string $resourcePath
     * @return void
     */
    public static function includeResource(string $resourcePath): void
    {
        require_once $resourcePath;
    }

	/**
	 * This will filter the resource path.
	 *
	 * @param string $resourcePath
	 * @return string|bool
	 */
    protected static function filterResourcePath(string $resourcePath)
    {
        // stop dir browsing
		$resourcePath = str_replace('.', '', $resourcePath);

		// remove query string
		$resourcePath = explode('?', $resourcePath)[0];

		// remove hash
		$resourcePath = explode('#', $resourcePath)[0];

		// remove last slash
		return preg_replace('/\/$/', '', $resourcePath);
    }
}
