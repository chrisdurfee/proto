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
	 * Redirect target URL.
	 *
	 * @var string
	 */
	protected string $redirectUrl;

	/**
	 * HTTP response code for the redirect.
	 *
	 * @var int
	 */
	protected int $responseCode;

	/**
	 * Creates a new redirect route.
	 *
	 * @param string $uri The URI to match.
	 * @param string $redirectUrl The URL to redirect to.
	 * @param int $responseCode The HTTP response code (default: 301).
	 */
	public function __construct(string $uri, string $redirectUrl, int $responseCode = 301)
	{
		parent::__construct($uri);
		$this->redirectUrl = $redirectUrl;
		$this->responseCode = $responseCode;
	}

	/**
	 * Activates the redirect route.
	 *
	 * @param string $request The incoming request URI.
	 * @return never
	 */
	public function activate(string $request): never
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