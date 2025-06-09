<?php declare(strict_types=1);
namespace Proto\Http\Middleware;

use Proto\Http\Router\Request;
use Proto\Http\Router\Response;
use Proto\Utils\Format\JsonFormat;

/**
 * class DomainMiddleware
 *
 * Middleware to check if the request is coming from the app domains.
 *
 * @package Proto\Http\Middleware
 */
class DomainMiddleware
{
	/**
	 * Checks if the request's origin or referer is allowed.
	 *
	 * @param Request $request
	 * @return bool
	 */
	protected function isSupportedDomain(Request $request): bool
	{
		$allowed = (array)env('urls');

		$originHeader = $request->header('origin')  ?? '';
		$refererHeader = $request->header('referer') ?? '';

		$originHost = $originHeader ? parse_url($originHeader,  PHP_URL_HOST) : null;
		$refererHost = $refererHeader ? parse_url($refererHeader, PHP_URL_HOST) : null;

		$hostHeader = $request->header('host')
			? strtolower(explode(':', $request->header('host'), 2)[0])
			: null;


		return (
			!in_array($originHost,  $allowed, true)
		 && !in_array($refererHost, $allowed, true)
		 && !in_array($hostHeader,  $allowed, true)
		);
	}

	/**
	 * Handles the request by checking the origin.
	 *
	 * @param Request $request The incoming request.
	 * @param callable $next The next middleware handler.
	 * @return mixed The processed request.
	 */
	public function handle(Request $request, callable $next): mixed
	{
		if (!$this->isSupportedDomain($request))
		{
			$FORBIDDEN_CODE = 403;
			$this->error('Domain not allowed', $FORBIDDEN_CODE);
			return null;
		}

		return $next($request);
	}

	 /**
	 * This will exit the application with a 403 response.
	 *
	 * @return void
	 */
	protected function error(string $msg, int $responseCode): void
	{
		$response = new Response();
		$response->render($responseCode);

		JsonFormat::encodeAndRender([
			'message' => 'The domain is not allowed.',
			'success' => false
		]);

		exit;
	}
}