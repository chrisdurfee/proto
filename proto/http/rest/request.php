<?php declare(strict_types=1);
namespace Proto\Http\Rest;

/**
 * Request
 *
 * This will handle the request.
 *
 * @package Proto\Http\Rest
 */
class Request
{
	/**
	 * @var string $username
	 */
	public string $username;

	/**
	 * @var string $password
	 */
	public string $password;

	/**
	 * @var array $error
	 */
	public static array $error = [];

	/**
	 * @var bool $debug
	 */
	protected bool $debug = false;

	/**
	 * This will set up the request.
	 *
	 * @param string $baseUrl
	 * @param array $headers
	 * @param bool $json
	 * @return void
	 */
	public function __construct(
		protected string $baseUrl = '',
		protected array $headers = [],
		public bool $json = false
	)
	{
	}

	/**
	 * This will set the authenitcation for the request.
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
	 * This will make a curl request.
	 *
	 * @param string $url
	 * @param string $method
	 * @param mixed $params
	 * @return object
	 */
	protected function createCurl(
		string $url,
		string $method,
		mixed $params = null
	): object
	{
		$curl = new Curl($this->debug);
		$curl->addHeaders($this->headers);

		if (isset($this->username) && isset($this->password))
		{
			$curl->setAuthentication($this->username, $this->password);
		}

		return $curl->request($url, $method, $params);
	}

	/**
	 * This will make the request.
	 *
	 * @param string $url
	 * @param string $method
	 * @param string $params
	 * @return Response
	 */
	public function request(
		string $url,
		string $method = 'post',
		mixed $params = null
	): Response
	{
		$url = $this->addBaseToUrl($url);
		$results = $this->createCurl($url, $method, $params);

		return new Response($results->code, $results->data, $this->json);
	}

	/**
	 * This will add the url to the base url.
	 *
	 * @param string $url
	 * @return string
	 */
	protected function addBaseToUrl(?string $url = null): string
	{
		if (!isset($url))
		{
			return $this->baseUrl;
		}

		if (!empty($this->baseUrl))
		{
			/* we want to check that the join area doesn't have
			a slash on both ends of the join */
			$url = (substr($this->baseUrl, -1, 1) == '/' && substr($url, 0, 1) == '/')?
				substr($this->baseUrl, 0, -1) . $url
			:
				$this->baseUrl . $url;
		}

		return $url;
	}

	/**
	 * This will make a get request.
	 *
	 * @param string|null $url
	 * @param mixed $params
	 * @return object
	 */
	public function get(?string $url = null, mixed $params = null): object
	{
		return $this->request($url, 'GET', $params);
	}

	/**
	 * This will make a post request.
	 *
	 * @param string|null $url
	 * @param mixed $params
	 * @return object
	 */
	public function post(?string $url = null, mixed $params = null): object
	{
		return $this->request($url, 'POST', $params);
	}

	/**
	 * This will make a patch request.
	 *
	 * @param string|null $url
	 * @param mixed $params
	 * @return object
	 */
	public function patch(?string $url = null, mixed $params = null): object
	{
		return $this->request($url, 'PATCH', $params);
	}

	/**
	 * This will make a put request.
	 *
	 * @param string|null $url
	 * @param mixed $params
	 * @return object
	 */
	public function put(?string $url = null, mixed $params = null): object
	{
		return $this->request($url, 'PUT', $params);
	}

	/**
	 * This will make a delete request.
	 *
	 * @param string|null $url
	 * @param mixed $params
	 * @return object
	 */
	public function delete(?string $url = null, mixed $params = null): object
	{
		return $this->request($url, 'DELETE', $params);
	}

	/**
	 * This will handle the errors.
	 *
	 * @param mixed $error
	 * @return void
	 */
	protected static function handleError($error): void
	{
		array_push(self::$error, $error);
	}

	/**
	 * This will get the last error.
	 *
	 * @return mixed
	 */
	public static function getLastError(): mixed
	{
		return (array_pop(self::$error));
	}
}