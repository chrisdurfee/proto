<?php declare(strict_types=1);
namespace Proto\Http\Rest;

/**
 * Request
 *
 * Handles HTTP requests with authentication and headers.
 *
 * @package Proto\Http\Rest
 */
class Request
{
	/**
	 * Username for authentication.
	 */
	public ?string $username = null;

	/**
	 * Password for authentication.
	 */
	public ?string $password = null;

	/**
	 * Stores errors encountered during requests.
	 */
	public static array $error = [];

	/**
	 * Enables debugging mode.
	 */
	protected bool $debug = false;

	/**
	 * Initializes a new request instance.
	 *
	 * @param string $baseUrl The base URL for the request.
	 * @param array $headers Headers to be sent with the request.
	 * @param bool $json Whether the response should be formatted as JSON.
	 */
	public function __construct(
		protected string $baseUrl = '',
		protected array $headers = [],
		public bool $json = false
	)
	{
	}

	/**
	 * Sets authentication credentials.
	 *
	 * @param string $username
	 * @param string $password
	 * @return void
	 */
	public function setAuthentication(string $username, string $password): void
	{
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * Creates a cURL request instance.
	 *
	 * @param string $url The request URL.
	 * @param string $method The HTTP method.
	 * @param mixed $params Request parameters.
	 * @return object The response object.
	 */
	protected function createCurl(
		string $url,
		string $method,
		mixed $params = null
	): object
	{
		$curl = new Curl($this->debug);
		$curl->addHeaders($this->headers);

		if (!empty($this->username) && !empty($this->password))
		{
			$curl->setAuthentication($this->username, $this->password);
		}

		return $curl->request($url, $method, $params);
	}

	/**
	 * Makes an HTTP request.
	 *
	 * @param string $url The request URL.
	 * @param string $method The HTTP method.
	 * @param mixed $params Request parameters.
	 * @return Response The response object.
	 */
	public function request(
		string $url,
		string $method = 'POST',
		mixed $params = null
	): Response
	{
		$url = $this->addBaseToUrl($url);
		$results = $this->createCurl($url, strtoupper($method), $params);

		return new Response($results->code, $results->data, $this->json);
	}

	/**
	 * Combines the base URL with the provided endpoint.
	 *
	 * @param string|null $url The endpoint URL.
	 * @return string The full request URL.
	 */
	protected function addBaseToUrl(?string $url = null): string
	{
		if (empty($url))
		{
			return $this->baseUrl;
		}

		$base = rtrim($this->baseUrl, '/');
		$endpoint = ltrim($url, '/');

		return "{$base}/{$endpoint}";
	}

	/**
	 * Makes a GET request.
	 *
	 * @param string|null $url The request URL.
	 * @param mixed $params Request parameters.
	 * @return Response The response object.
	 */
	public function get(?string $url = null, mixed $params = null): Response
	{
		return $this->request($url ?? '', 'GET', $params);
	}

	/**
	 * Makes a POST request.
	 *
	 * @param string|null $url The request URL.
	 * @param mixed $params Request parameters.
	 * @return Response The response object.
	 */
	public function post(?string $url = null, mixed $params = null): Response
	{
		return $this->request($url ?? '', 'POST', $params);
	}

	/**
	 * Makes a PATCH request.
	 *
	 * @param string|null $url The request URL.
	 * @param mixed $params Request parameters.
	 * @return Response The response object.
	 */
	public function patch(?string $url = null, mixed $params = null): Response
	{
		return $this->request($url ?? '', 'PATCH', $params);
	}

	/**
	 * Makes a PUT request.
	 *
	 * @param string|null $url The request URL.
	 * @param mixed $params Request parameters.
	 * @return Response The response object.
	 */
	public function put(?string $url = null, mixed $params = null): Response
	{
		return $this->request($url ?? '', 'PUT', $params);
	}

	/**
	 * Makes a DELETE request.
	 *
	 * @param string|null $url The request URL.
	 * @param mixed $params Request parameters.
	 * @return Response The response object.
	 */
	public function delete(?string $url = null, mixed $params = null): Response
	{
		return $this->request($url ?? '', 'DELETE', $params);
	}

	/**
	 * Stores an error message.
	 *
	 * @param mixed $error The error to store.
	 * @return void
	 */
	protected static function handleError(mixed $error): void
	{
		self::$error[] = $error;
	}

	/**
	 * Retrieves the last error.
	 *
	 * @return mixed The last stored error.
	 */
	public static function getLastError(): mixed
	{
		return array_pop(self::$error) ?: null;
	}
}