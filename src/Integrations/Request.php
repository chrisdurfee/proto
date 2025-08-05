<?php declare(strict_types=1);
namespace Proto\Integrations;

use Proto\Http\Rest\Request as Rest;

/**
 * Class Request
 *
 * This will setup the request object.
 *
 * @package Proto\Integrations
 */
class Request
{
	/**
	 * URL.
	 *
	 * @var string
	 */
	protected string $url = '';

	/**
	 * Whether to add credentials.
	 *
	 * @var bool
	 */
	public bool $addCredentials = false;

	/**
	 * Headers.
	 *
	 * @var array
	 */
	public array $headers = [];

	/**
	 * Username.
	 *
	 * @var string|null
	 */
	public ?string $username = null;

	/**
	 * Password.
	 *
	 * @var string|null
	 */
	public ?string $password = null;

	/**
	 * Constructor.
	 *
	 * @param string|null $url
	 * @param array|null $headers
	 * @return void
	 */
	public function __construct(?string $url = '', ?array $headers = [])
	{
		$this->setUrl($url);
		$this->setHeaders($headers);
	}

	/**
	 * Sets the URL.
	 *
	 * @param string $url
	 * @return void
	 */
	public function setUrl(string $url): void
	{
		$this->url = $url;
	}

	/**
	 * Sets up the default headers.
	 *
	 * @param array|null $headers
	 * @return void
	 */
	protected function setHeaders(?array $headers = null): void
	{
		$this->headers = $headers ?? [
			'Content-Type' => 'application/x-www-form-urlencoded'
		];
	}

	/**
	 * Sets up the headers.
	 *
	 * @param array|null $headers
	 * @return array
	 */
	protected function setupHeaders(?array $headers = []): array
	{
		if (empty($headers))
		{
			return $this->headers;
		}
		return $headers;
	}

	/**
	 * Creates the REST object.
	 *
	 * @param array|null $headers
	 * @param string|null $url
	 * @return Rest
	 */
	public function createRest(?array $headers = [], ?string $url = null): Rest
	{
		$headers = $this->setupHeaders($headers);
		$url = $url ?? $this->url;
		return new Rest($url, $headers, true);
	}

	/**
	 * Makes a REST request.
	 *
	 * @param string|null $method
	 * @param string|null $url
	 * @param string $params
	 * @param array|null $headers
	 * @return object
	 */
	public function send(?string $method = 'GET', ?string $url = '', string $params = '', ?array $headers = []): object
	{
		$api = $this->createRest($headers);
		if ($this->addCredentials)
		{
			$api->username = $this->username;
			$api->password = $this->password;
		}

		switch (strtolower($method))
		{
			case 'post':
				$result = $api->post($url, $params);
				break;
			case 'patch':
				$result = $api->patch($url, $params);
				break;
			case 'put':
				$result = $api->put($url, $params);
				break;
			case 'delete':
				$result = $api->delete($url, $params);
				break;
			default:
				$result = $api->get($url, $params);
		}
		return $result;
	}
}