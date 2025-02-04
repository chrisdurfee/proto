<?php declare(strict_types=1);
namespace Proto\Integrations;

use Proto\Http\Rest\Request as Rest;

/**
 * Request
 *
 * This will setup the request object.
 *
 * @package Proto\Integrations
 */
class Request
{
	/**
	 * @var string $url
	 */
	protected $url = '';

	/**
	 * @var bool $addCredentials
	 */
	public $addCredentials = false;

	/**
	 * @var array $headers
	 */
	public array $headers = [];

	/**
	 * @var string|null $username
	 */
	public ?string $username = null;

	/**
	 * @var string|null $password
	 */
	public ?string $password = null;

	/**
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
	 * This will set the url.
	 *
	 * @param string $url
	 * @return void
	 */
	public function setUrl(string $url)
	{
		$this->url = $url;
	}

	/**
	 * This will setup the deafult headers.
	 *
	 * @param array|null $headers
	 * @return void
	 */
	protected function setHeaders(?array $headers = null)
	{
		$this->headers = $headers ?? [
			'Content-Type' => 'application/x-www-form-urlencoded'
		];
	}

	/**
	 * This will setup the headers.
	 *
	 * @param array|null $headers
	 * @return array
	 */
	protected function setupHeaders(?array $headers = [])
	{
		if (sizeof($headers) < 1)
		{
			$headers = $this->headers;
		}
		return $headers;
	}

	/**
	 * This will create the rest object.
	 *
	 * @param array|null $headers
	 * @param string|null $url
	 * @return Rest
	 */
	public function createRest(?array $headers = [], ?string $url = null)
	{
		$headers = $this->setupHeaders($headers);
		$url = $url ?? $this->url;

		return new Rest($url, $headers, true);
	}

	/**
	 * This will make a rest request.
	 *
	 * @param string|null $method
	 * @param string|null $url
	 * @param string $params
	 * @param array|null $headers
	 * @return object
	 */
	public function send(?string $method = 'GET', ?string $url = '', $params = '', ?array $headers = []): object
	{
		$api = $this->createRest($headers);

		if ($this->addCredentials == true)
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