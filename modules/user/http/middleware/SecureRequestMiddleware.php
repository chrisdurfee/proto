<?php declare(strict_types=1);
namespace Modules\User\Http\Middleware;

use Proto\Http\Router\Request;
use Modules\User\Auth\Gates\SecureRequestGate;
use Proto\Http\Router\Response;
use Proto\Utils\Format\JsonFormat;

/**
 * SecureRequestMiddleware
 *
 * Middleware to secure requests by validating the request id to a user id
 *
 * @package Modules\Auth\Http\Middleware
 */
class SecureRequestMiddleware
{
	/**
	 * Handles incoming requests.
	 *
	 * @param Request $request The incoming request.
	 * @param callable $next The next middleware handler.
	 * @return mixed The processed request.
	 */
	public function handle(Request $request, callable $next): mixed
	{
		$requestId = $request->input('requestId') ?? $request->input('token');
		if (!isset($requestId))
		{
			self::exitWithResponse();
			return false;
		}

		$userId = $request->params()->id ?? $request->input('userId') ?? null;
		if (!isset($userId))
		{
			self::exitWithResponse();
			return false;
		}

		$gate = new SecureRequestGate();
		if ($gate->isValid($requestId, $userId) === false)
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
		$responseCode = 403;
		$response = new Response();
		$response->render($responseCode);

		JsonFormat::encodeAndRender([
			'message' => 'The secure request is invalid.',
			'success' => false
		]);

		exit;
	}
}