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
	 * @return never
	 */
	protected function sendRedirect(): never
	{
		$response = new Response();
		$response->render($this->responseCode);

		header('Location: ' . $this->redirectUrl, true, $this->responseCode);
		exit;
	}
}