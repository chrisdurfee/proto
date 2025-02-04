<?php declare(strict_types=1);
namespace Proto\API;

use Proto\Base;
use Proto\Utils\Strings;
use Proto\Http\Request;
use Proto\Http\Response;
use Proto\Api\ResponseMessage;
use Proto\Http\Router\Router;
use Proto\Auth\CrossSiteRequestForgeryGate as CSRF;
use Proto\Config;
use Proto\Tests\Debug;
use Proto\Http\Middleware\ApiRateLimiterMiddleware;
use Proto\Error\Error;

/**
 * This will get the action by request method.
 *
 * @param string $method
 * @return string|null
 */
function actionByRequestMethod(string $method): ?string
{
	switch($method)
	{
		case 'GET':
			return 'get';
		case 'POST':
			return 'add';
		case 'PUT':
			return 'setup';
		case 'PATCH':
			return 'update';
		case 'DELETE':
			return 'delete';
		case 'OPTIONS':
			die();
	}
	return null;
}

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
 * ApiController
 *
 * This will create a resource API that uses the Proto\Http\Router\Router
 * to route to service files as end points.
 *
 * @package Proto\API
 */
class ApiController extends Base
{
	/**
	 * @var ServiceInterface|null $service
	 */
	protected ?ServiceInterface $service;

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
	public function setupApi(): void
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
    public static function initializeAPI(): void
    {
		$api = new static();
		$api->setupApi();
	}

	/**
	 * This will get the request action.
	 *
	 * @return mixed
	 */
    public function getAction(): mixed
    {
		$method = $this->getRequestMethod();
		$action = Request::input('op') ?? Request::input('action') ?? actionByRequestMethod($method);
        if (!isset($action))
		{
			/* no action was specified */
			showMessage('3');
			return null;
		}

		if (!$this->checkCallableAction($action))
		{
			/* the action is not found */
			showMessage('4');
			return null;
		}

		$service = $this->service;

		if ($this->checkCSRF($method) === false)
		{
			if($service->isOpenMethod($action) === false)
			{
				showMessage('9');
				return null;
			}
		}

		try
		{
			return call_user_func([$service, $action]);
		}
		catch (\Throwable $e)
		{
			showMessage('12');
			self::handleError($e);
			return null;
		}
    }

	/**
	 * This will check if the method type is a safe method.
	 *
	 * @param string $method
	 * @return bool
	 */
	protected function isSafeMethod(string $method): bool
	{
		return in_array($method, ['OPTIONS', 'HEAD', 'GET']);
	}

	/**
	 * This will check the request token.
	 *
	 * @param string $method
	 * @return bool
	 */
	protected function checkCSRF(string $method): bool
	{
		if ($this->isSafeMethod($method) === true)
		{
			return true;
		}

		if ($this->isDevEnv())
		{
			return true;
		}

		$gate = new CSRF();
		return $gate->isValid();
	}

	/**
	 * This will check if the env is dev.
	 *
	 * @return bool
	 */
	protected function isDevEnv(): bool
	{
		return (Config::access('env') === 'dev');
	}

	/**
	 * This will check if the action is callable.
	 *
	 * @param string $action
	 * @return bool
	 */
    protected function checkCallableAction(string $action): bool
    {
        return method_exists($this->service, $action);
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
		$this->router = new Router('/');
	}

	/**
	 * This will add the api routes to the router.
	 *
	 * @return void
	 */
	protected function addRoutes(): void
	{
		$middleware = [ApiRateLimiterMiddleware::class];

		$self = $this;
		$router = $this->router;
		$router->all('/api/:service.*', function($req, $params) use($self)
		{
			$service = $params->service ?? null;
			if (empty($service))
			{
				/* no child api was found */
				showMessage('1');
				return;
			}

			/* this will remove the trailing slash */
			if (substr($service, -1) === '/')
			{
				$service = substr($service, 0, -1);
			}

			return $self->activateService($service);
		}, $middleware);
	}

	/**
	 * This will get the service class name.
	 *
	 * @param string $apiName
	 * @return string
	 */
	protected function getServiceClassName(string $apiName): string
	{
		return "\\App\\API\\$apiName";
	}

	/**
	 * This will activate the service.
	 *
	 * @param string $service
	 * @return mixed
	 */
	public function activateService(string $service): mixed
	{
		/* we want to get the api name from the apiTarget or
		uri file name */
        $apiName = self::getServiceApi($service);
        if (!$apiName)
		{
			/* no child api was found */
			showMessage('1');
			return false;
		}

		$this->service = $this->getService($apiName);
		return $this->getAction();
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
	 * This will get the service.
	 *
	 * @param string $apiName
	 * @return object
	 */
	protected function getService(string $apiName): object
	{
		$className = $this->getServiceClassName($apiName);
		if (\class_exists($className) !== true)
		{
			showMessage('2');
			die;
		}

		try
		{
			/**
			 * @var object $className
			 */
			return new $className();
		}
		catch (\Throwable $e)
		{
			showMessage('2');
			self::handleError($e);
			die;
		}
	}

	/**
	 * This will get the api service.
	 *
	 * @param string $service
	 * @return string|bool
	 */
    protected static function getServiceApi(string $service)
    {
        // stop dir browsing
		$service = str_replace('.', '', $service);

		// remove query string
		$service = explode('?', $service)[0];

		// remove hash
		$service = explode('#', $service)[0];

		// remove last slash
		$service = preg_replace('/\/$/', '', $service);

		/* we want to add support to autoload namespaced classes */
		$className = str_replace('/', '\\', $service);
		return Strings::pascalCase($className) . 'API';
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
