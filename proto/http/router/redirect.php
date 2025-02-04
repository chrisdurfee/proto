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
	 * @var string $redirectUrl
	 */
    protected string $redirectUrl;

    /**
     * @var int $responseCode
     */
    protected int $responseCode;

    /**
     * This will create a new redirect route.
     *
     * @param string $uri
     * @param string $redirectUrl
     * @param int $responseCode
     * @return void
     */
	public function __construct(string $uri, string $redirectUrl, int $responseCode = 301)
	{
        parent::__construct($uri);

        $this->redirectUrl = $redirectUrl;
        $this->responseCode = $responseCode;
    }

    /**
     * This will activate the route.
     *
     * @param string $request
     * @return mixed
     */
    public function activate(string $request): mixed
    {
        $response = new Response();
        $response->render($this->responseCode);

        header('Location: ' . $this->redirectUrl);
        die;
    }
}