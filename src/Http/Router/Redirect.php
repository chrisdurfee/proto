<?php declare(strict_types=1);
namespace Proto\Http\Router;

/**
 * Redirect
 *
 * This class represents a redirect route.
 *
 * @package Proto\Http\Router
 */
class Redirect extends Uri
{
	/**
	 * Creates a new redirect route.
	 *
	 * @param string $uri The URI to match.
	 * @param string $redirectUrl The URL to redirect to.
	 * @param int $responseCode The HTTP response code (default: 301).
	 * @return void
	 */
	public function __construct(
		string $uri,
		protected string $redirectUrl,
		protected int $responseCode = 301
	)
	{
		parent::__construct($uri);
	}

	/**
	 * Activates the redirect route.
	 *
	 * @param Request $request The incoming request URI.
	 * @return never
	 */
	public function activate(Request $request): never
	{
		$this->sendRedirect();
	}

	/**
	 * Sends the redirect response.
	 *
	 * The redirect URL is sanitized to strip CR/LF characters, preventing
	 * HTTP response-splitting attacks.
	 *
	 * @return never
	 */
	protected function sendRedirect(): never
	{
		$response = new Response();
		$response->render($this->responseCode);

		$safeUrl = str_replace(["\r", "\n", "\0"], '', $this->redirectUrl);
		header('Location: ' . $safeUrl, true, $this->responseCode);
		exit;
	}
}