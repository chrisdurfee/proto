<?php declare(strict_types=1);
namespace Proto\Integrations;

/**
 * Class RestService
 *
 * This will set up a service that uses REST.
 *
 * @package Proto\Integrations
 * @abstract
 */
abstract class RestService extends Service
{
	/**
	 * API client object.
	 *
	 * @var object|null
	 */
	protected ?object $api = null;

	/**
	 * The service response data type.
	 *
	 * @var string
	 */
	protected string $responseFormat = 'json';

	/**
	 * Constructor.
	 *
	 * Sets up the service.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->setupRequest();
	}

	/**
	 * Sets up the headers.
	 *
	 * @return array
	 */
	protected function setupHeaders(): array
	{
		return [
			'Content-Type' => 'application/x-www-form-urlencoded'
		];
	}

	/**
	 * Sets up the service request.
	 *
	 * @return void
	 */
	protected function setupRequest(): void
	{
		$headers = $this->setupHeaders();
		$this->api = new Request($this->url, $headers);
	}

	/**
	 * Creates the REST used to curl the requests.
	 *
	 * @param array|null $headers
	 * @param string|null $url
	 * @return object
	 */
	public function createRest(?array $headers = [], ?string $url = ''): object
	{
		return $this->api->createRest($headers, $url);
	}

	/**
	 * Makes a REST request.
	 *
	 * @param string|null $method
	 * @param string|null $url
	 * @param mixed $params
	 * @param array|null $headers
	 * @return object
	 */
	public function request(
		?string $method = 'GET',
		?string $url = '',
		mixed $params = '',
		?array $headers = []
	): object {
		return $this->api->send($method, $url, $params, $headers);
	}

	/**
	 * Fetches a request and checks the result.
	 *
	 * @param string|null $method
	 * @param string|null $url
	 * @param mixed $params
	 * @param array|null $headers
	 * @param string|null $responseCode
	 * @return object
	 */
	public function fetch(
		?string $method = 'GET',
		?string $url = '',
		mixed $params = '',
		?array $headers = [],
		?string $responseCode = '200'
	): object {
		$result = $this->request($method, $url, $params, $headers);
		return $this->setupResponse($responseCode, $result, $this->responseFormat);
	}

	/**
	 * Sets up a response object.
	 *
	 * @param string|null $code
	 * @param object|null $result
	 * @param string|null $format
	 * @return object
	 */
	protected function setupResponse(?string $code = '200', $result = null, ?string $format = 'json'): object
	{
		if (!$result)
		{
			return $this->error('no result from service.');
		}

		$data = $result->data ?? $result;
		if ($format === 'json' && gettype($data) === 'string')
		{
			$data = self::decode($data);
		}

		$response = $this->prepareResponse($data);
		return ($result->code != $code) ? $this->error('The API returned an error.', $data) : $this->response($response);
	}

	/**
	 * Prepares the response.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	protected function prepareResponse($data)
	{
		return $data;
	}

	/**
	 * Sets up the user credentials.
	 *
	 * @param string $username
	 * @param string $password
	 * @return void
	 */
	protected function setupUserCredentials(string $username, string $password): void
	{
		$this->api->username = $username;
		$this->api->password = $password;
	}
}