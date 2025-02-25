<?php declare(strict_types=1);
namespace
{
	use Proto\Http\Router\Router;

	$basePath = env('router')->basePath ?? '/';
	$router = new Router($basePath);

	/**
	 * This will return the router instance.
	 *
	 * @return Router
	 */
	function router(): Router
	{
		global $router;
		return $router;
	}
}

namespace Proto\Api
{
	use Proto\Base;
	use Proto\Http\Response;
	use Proto\Http\Router\Router;
	use Proto\Http\Middleware\CrossSiteProtectionMiddleware;
	use Proto\Http\Middleware\ApiRateLimiterMiddleware;

	/**
	 * Class ApiRouter
	 *
	 * Handles API routing and middleware setup.
	 *
	 * @package Proto\Api
	 */
	class ApiRouter extends Base
	{
		/**
		 * HTTP status code for Not Found.
		 *
		 * @var int
		 */
		private const HTTP_NOT_FOUND = 404;

		/**
		 * The router instance.
		 *
		 * @var Router
		 */
		protected Router $router;

		/**
		 * Sets up the API service by initializing the router and adding routes.
		 *
		 * @return void
		 */
		public function setup(): void
		{
			$this->setupRouter();
			$this->addRoutes();
		}

		/**
		 * Initializes the RouterHelper instance and sets up the API service.
		 *
		 * @return void
		 */
		public static function initialize(): void
		{
			$api = new static();
			$api->setup();
		}

		/**
		 * Sets up the router using the base path from the environment configuration.
		 *
		 * @return void
		 */
		protected function setupRouter(): void
		{
			$this->router = router();
		}

		/**
		 * Adds API routes to the router with the appropriate middleware.
		 *
		 * @return void
		 */
		protected function addRoutes(): void
		{
			$middleware = [
				CrossSiteProtectionMiddleware::class,
				ApiRateLimiterMiddleware::class,
			];

			$this->router->all('/api/:resource.*', function ($req, $params) use ($middleware)
			{
				$resource = $params->resource ?? null;
				if (empty($resource))
				{
					return self::error('No resource was specified.', self::HTTP_NOT_FOUND);
				}

				$resourcePath = ResourceHelper::getResource($resource);
				if (! $resourcePath)
				{
					return self::error('The resource path is not valid.', self::HTTP_NOT_FOUND);
				}

				ResourceHelper::includeResource($resourcePath);
			}, $middleware);
		}

		/**
		 * Creates an error response.
		 *
		 * @param string $message The error message.
		 * @param int $httpCode The HTTP status code.
		 * @return Response The constructed error response.
		 */
		public static function error(string $message, int $httpCode): Response
		{
			return new Response(
				(object)[
					'message' => $message,
					'success' => false
				],
				$httpCode
			);
		}
	}
}