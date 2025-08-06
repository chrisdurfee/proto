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

		$allowedHosts = array_map(fn($u) =>
			parse_url($u, PHP_URL_HOST) ?: strtolower($u),
			$allowed
		);

		$hostHeader = $request->header('host');
		$hostOnly = strtolower(explode(':',$hostHeader)[0]);

		return in_array($hostOnly, $allowedHosts, true);
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
		$origin = $request->header('origin');
		if ($origin === null)
		{
			return $next($request);
		}

		if (!$this->isSupportedDomain($request))
		{
			$this->error('Domain not allowed', 403);
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