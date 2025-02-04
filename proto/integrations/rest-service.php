<?php declare(strict_types=1);
namespace Proto\Integrations;

/**
 * RestService
 *
 * This will set up a service that uses rest.
 *
 * @package Proto\Integrations
 * @abstract
 */
abstract class RestService extends Service
{
	/**
	 * @var object|null $api
	 */
	protected ?object $api = null;

	/**
	 * This will setup the service.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setupRequest();
	}

	/**
	 * This is the service response data type.
	 *
	 * @var string $responseFormat
	 */
	protected $responseFormat = 'json';

	/**
	 * This will setup the headers.
	 *
	 * @return array
	 */
	protected function setupHeaders()
	{
		return [
			'Content-Type' => 'application/x-www-form-urlencoded'
		];
	}

	/**
	 * This will setup the service request.
	 *
	 * @return void
	 */
	protected function setupRequest()
	{
		$headers = $this->setupHeaders();
		$this->api = new Request($this->url, $headers);
	}

	/**
	 * This will create the the rest used to curl the requests.
	 *
	 * @param array|null $headers
	 * @param string|null $url
	 * @return object
	 */
	public function createRest(?array $headers = [], ?string $url = '')
	{
		return $this->api->createRest($headers, $url);
	}

	/**
	 * This will make a rest request.
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
	)
	{
		return $this->api->send($method, $url, $params, $headers);
	}

	/**
	 * This will fetch a request and check the result.
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
	)
	{
		$result = $this->request($method, $url, $params, $headers);
		return $this->setupResponse($responseCode, $result, $this->responseFormat);
	}

	/**
	 * This will setup a response object.
	 *
	 * @param string|null $code
	 * @param object|null $result
	 * @param string|null $format
	 * @return object
	 */
	protected function setupResponse(?string $code = '200', $result = null, ?string $format = 'json')
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
		return ($result->code != $code)? $this->error('The API returned an error.', $data) : $this->response($response);
	}

	/**
	 * This will prepare the response
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	protected function prepareResponse($data)
	{
		return $data;
	}

	/**
	 * This will set up the user credentials.
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