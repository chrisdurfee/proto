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
	 * Builds the list of allowed hosts from the `domain` config.
	 *
	 * @return array<int, string>
	 */
	protected function allowedHosts(): array
	{
		$domainConfig = (array)env('domain');

		// Get base domains (strings only)
		$baseDomains = array_map(fn($u) =>
			strtolower(parse_url($u, PHP_URL_HOST) ?: $u),
			array_filter($domainConfig, 'is_string')
		);

		// Get subdomains if defined
		$subdomains = isset($domainConfig['subdomains']) ? (array)$domainConfig['subdomains'] : [];

		// Build full list of allowed hosts
		$allowedHosts = $baseDomains;
		foreach ($baseDomains as $domain)
		{
			foreach ($subdomains as $sub)
			{
				if (is_string($sub))
				{
					$allowedHosts[] = strtolower($sub . '.' . $domain);
				}
			}
		}

		return array_values($allowedHosts);
	}

	/**
	 * Lower-cased host of a URL, or null when it has none.
	 *
	 * @param string $url
	 * @return string|null
	 */
	protected function hostOf(string $url): ?string
	{
		$host = parse_url($url, PHP_URL_HOST);
		return is_string($host) && $host !== '' ? strtolower($host) : null;
	}

	/**
	 * Checks if the request's origin or referer is allowed.
	 *
	 * The caller is identified by its Origin header, or its Referer when
	 * no Origin is sent. The API's own Host header says nothing about the
	 * caller, so it only counts as allowed for same-origin requests.
	 * Requests with neither header (server-to-server, CLI) are allowed;
	 * `Origin: null` (sandboxed frames, file://) is not.
	 *
	 * @param Request $request
	 * @return bool
	 */
	protected function isSupportedDomain(Request $request): bool
	{
		$source = $request->header('origin');
		if ($source === null || $source === '')
		{
			$source = $request->header('referer');
		}

		if ($source === null || $source === '')
		{
			return true;
		}

		$sourceHost = $this->hostOf((string)$source);
		if ($sourceHost === null)
		{
			return false;
		}

		$allowedHosts = $this->allowedHosts();
		$ownHost = strtolower(explode(':', (string)($request->header('host') ?? ''))[0]);
		if ($ownHost !== '')
		{
			$allowedHosts[] = $ownHost;
		}

		return in_array($sourceHost, $allowedHosts, true);
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
		$message = (object)[
			'message' => 'The domain is not allowed.',
			'success' => false
		];

		(new Response())->json($message, $responseCode);

		exit;
	}
}
