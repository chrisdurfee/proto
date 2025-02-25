<?php declare(strict_types=1);
namespace Proto\API;

use Proto\Base;
use Proto\Http\Request;
use Proto\Http\Response;
use Proto\Api\ResponseMessage;
use Proto\Http\Router\Router;
use Proto\Config;
use Proto\Tests\Debug;
use Proto\Http\Middleware\CrossSiteProtectionMiddleware;
use Proto\Http\Middleware\ApiRateLimiterMiddleware;
use Proto\Error\Error;

/**
 * This will display a response message.
 *
 * @param string $code
 * @return Response
 */
function showMessage(string $code = '0'): Response
{
	$response = ResponseMessage::getMessage($code);
	return new Response($response, $response->response);
}

/**
 * RouterHelper
 *
 * This will help with the router.
 *
 * @package Proto\API
 */
class RouterHelper extends Base
{
	/**
	 * @var Router $router
	 */
	protected Router $router;

	/**
	 * @var array $errors
	 */
    protected static array $errors = [];

	/**
	 * This will setup the api service.
	 *
	 * @return void
	 */
	public function setup(): void
	{
		$this->setupRouter();
		$this->addRoutes();
	}

	/**
	 * This will get the request method.
	 *
	 * @return string
	 */
	protected function getRequestMethod(): string
	{
		return Request::method();
	}

	/**
	 * This will setup the api service.
	 *
	 * @return void
	 */
    public static function initialize(): void
    {
		$api = new static();
		$api->setup();
	}

	/**
	 * This will get the last error.
	 *
	 * @return mixed
	 */
    public static function getLastError(): mixed
    {
        return (array_pop(self::$errors));
    }

	/**
	 * This will setup the router.
	 *
	 * @return void
	 */
	protected function setupRouter(): void
	{
        $basePath = env('router')->basePath ?? '/';
		$this->router = new Router($basePath);
	}

	/**
	 * This will add the api routes to the router.
	 *
	 * @return void
	 */
	protected function addRoutes(): void
	{
		$middleware = [
            CrossSiteProtectionMiddleware::class,
            ApiRateLimiterMiddleware::class,
        ];

		$self = $this;
		$router = $this->router;
		$router->all('/api/:resource.*', function($req, $params) use($self)
		{
			$resource = $params->resource ?? null;
			if (empty($resource))
			{
				/* no child api was found */
				showMessage('1');
				return;
			}

			$resourcePath = ResourceHelper::getResource($resource);
			if (!$resourcePath) {
				showMessage('Resource not found.');
				return;
			}


			ResourceHelper::includeResource($resourcePath);
		}, $middleware);
	}

	/**
	 * This will check to render the error
	 *
	 * @param object $e
	 * @return void
	 */
	protected static function debug(object $e): void
	{
		if (Config::errors())
		{
			Debug::render((string)$e);
		}
	}

	/**
	 * This will handle an error.
	 *
	 * @param \throwable $error
	 * @return void
	 */
	protected static function handleError(\throwable $error): void
    {
		Error::exceptionHandler($error);
		self::debug($error);
        array_push(self::$errors, $error);
    }
}
