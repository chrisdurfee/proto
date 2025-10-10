<?php declare(strict_types=1);
namespace Proto\Http\Middleware;

use Proto\Auth\Gates\CrossSiteRequestForgeryGate as CSRF;
use Proto\Http\Router\Response;
use Proto\Http\Router\Request;

/**
 * CrossSiteProtectionMiddleware
 *
 * Middleware to protect against cross-site request forgery (CSRF).
 *
 * @package Proto\Http\Middleware
 */
class CrossSiteProtectionMiddleware
{
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
	 * Handles incoming requests.
	 *
	 * @param Request $request The incoming request.
	 * @param callable $next The next middleware handler.
	 * @return mixed The processed request.
	 */
	public function handle(Request $request, callable $next): mixed
	{
        $method = $request->method();
		if ($this->isSafeMethod($method) === true)
		{
			return $next($request);
		}

		$gate = new CSRF();
		if ($gate->isValid() === false)
        {
            self::exitWithResponse();
        }

		return $next($request);
	}

    /**
	 * This will exit the application with a 403 response.
	 *
	 * @return void
	 */
	protected static function exitWithResponse(): void
	{
		$UNAUTHORIZED_CODE = 403;
		$message = (object)[
			'message' => 'The CSRF token is invalid.',
			'success' => false
		];

		(new Response())->json($message, $UNAUTHORIZED_CODE);

		exit;
	}
}