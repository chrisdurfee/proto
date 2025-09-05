<?php declare(strict_types=1);
namespace Proto\Tests\Traits;

use Proto\Http\Request;
use Proto\Http\Response;
use Proto\Http\Router\Router;
use Proto\Tests\TestResponse;
use Proto\Http\Session;

/**
 * HttpTestHelpers
 *
 * Provides HTTP request testing utilities for test cases.
 *
 * @package Proto\Tests\Traits
 */
trait HttpTestHelpers
{
	/**
	 * @var array $defaultHeaders
	 */
	protected array $defaultHeaders = [
		'Accept' => 'application/json',
		'Content-Type' => 'application/json'
	];

	/**
	 * @var array $sessionData
	 */
	protected array $sessionData = [];

	/**
	 * @var object|null $authenticatedUser
	 */
	protected ?object $authenticatedUser = null;

	/**
	 * @var string|null $authToken
	 */
	protected ?string $authToken = null;

	/**
	 * Makes a GET request.
	 *
	 * @param string $uri
	 * @param array $headers
	 * @return TestResponse
	 */
	protected function get(string $uri, array $headers = []): TestResponse
	{
		return $this->makeRequest('GET', $uri, [], $headers);
	}

	/**
	 * Makes a POST request.
	 *
	 * @param string $uri
	 * @param array $data
	 * @param array $headers
	 * @return TestResponse
	 */
	protected function post(string $uri, array $data = [], array $headers = []): TestResponse
	{
		return $this->makeRequest('POST', $uri, $data, $headers);
	}

	/**
	 * Makes a PUT request.
	 *
	 * @param string $uri
	 * @param array $data
	 * @param array $headers
	 * @return TestResponse
	 */
	protected function put(string $uri, array $data = [], array $headers = []): TestResponse
	{
		return $this->makeRequest('PUT', $uri, $data, $headers);
	}

	/**
	 * Makes a PATCH request.
	 *
	 * @param string $uri
	 * @param array $data
	 * @param array $headers
	 * @return TestResponse
	 */
	protected function patch(string $uri, array $data = [], array $headers = []): TestResponse
	{
		return $this->makeRequest('PATCH', $uri, $data, $headers);
	}

	/**
	 * Makes a DELETE request.
	 *
	 * @param string $uri
	 * @param array $headers
	 * @return TestResponse
	 */
	protected function delete(string $uri, array $headers = []): TestResponse
	{
		return $this->makeRequest('DELETE', $uri, [], $headers);
	}

	/**
	 * Makes a GET JSON request.
	 *
	 * @param string $uri
	 * @param array $headers
	 * @return TestResponse
	 */
	protected function getJson(string $uri, array $headers = []): TestResponse
	{
		return $this->get($uri, array_merge($this->defaultHeaders, $headers));
	}

	/**
	 * Makes a POST JSON request.
	 *
	 * @param string $uri
	 * @param array $data
	 * @param array $headers
	 * @return TestResponse
	 */
	protected function postJson(string $uri, array $data = [], array $headers = []): TestResponse
	{
		return $this->post($uri, $data, array_merge($this->defaultHeaders, $headers));
	}

	/**
	 * Makes a PUT JSON request.
	 *
	 * @param string $uri
	 * @param array $data
	 * @param array $headers
	 * @return TestResponse
	 */
	protected function putJson(string $uri, array $data = [], array $headers = []): TestResponse
	{
		return $this->put($uri, $data, array_merge($this->defaultHeaders, $headers));
	}

	/**
	 * Makes a PATCH JSON request.
	 *
	 * @param string $uri
	 * @param array $data
	 * @param array $headers
	 * @return TestResponse
	 */
	protected function patchJson(string $uri, array $data = [], array $headers = []): TestResponse
	{
		return $this->patch($uri, $data, array_merge($this->defaultHeaders, $headers));
	}

	/**
	 * Makes a DELETE JSON request.
	 *
	 * @param string $uri
	 * @param array $headers
	 * @return TestResponse
	 */
	protected function deleteJson(string $uri, array $headers = []): TestResponse
	{
		return $this->delete($uri, array_merge($this->defaultHeaders, $headers));
	}

	/**
	 * Sets the authenticated user for subsequent requests.
	 *
	 * @param object $user
	 * @return self
	 */
	protected function actingAs(object $user): self
	{
		$this->authenticatedUser = $user;
		return $this;
	}

	/**
	 * Sets the authentication token for subsequent requests.
	 *
	 * @param string $token
	 * @return self
	 */
	protected function withToken(string $token): self
	{
		$this->authToken = $token;
		return $this;
	}

	/**
	 * Sets session data for subsequent requests.
	 *
	 * @param array $data
	 * @return self
	 */
	protected function withSession(array $data): self
	{
		$this->sessionData = array_merge($this->sessionData, $data);
		return $this;
	}

	/**
	 * Sets default headers for requests.
	 *
	 * @param array $headers
	 * @return self
	 */
	protected function withHeaders(array $headers): self
	{
		$this->defaultHeaders = array_merge($this->defaultHeaders, $headers);
		return $this;
	}

	/**
	 * Makes an HTTP request.
	 *
	 * @param string $method
	 * @param string $uri
	 * @param array $data
	 * @param array $headers
	 * @return TestResponse
	 */
	protected function makeRequest(string $method, string $uri, array $data = [], array $headers = []): TestResponse
	{
		// Prepare headers
		$requestHeaders = array_merge($this->defaultHeaders, $headers);

		// Add authentication headers
		if ($this->authToken)
		{
			$requestHeaders['Authorization'] = 'Bearer ' . $this->authToken;
		}

		// Setup request environment
		$this->setupRequestEnvironment($method, $uri, $data, $requestHeaders);

		// Create request and response objects
		$request = new Request();
		$response = new Response();

		try
		{
			// Setup session if needed
			if (!empty($this->sessionData) || $this->authenticatedUser)
			{
				$this->setupTestSession($request);
			}

			// Execute the request through the router
			$router = new Router();
			$result = $this->executeRequest($router, $request, $method, $uri);

			// Determine status code
			$statusCode = $this->extractStatusCode($result);

			return new TestResponse($response, $result, $statusCode, $this->getResponseHeaders());

		}
		catch (\Exception $e)
		{
			// Handle exceptions and return error response
			return new TestResponse($response, ['error' => $e->getMessage()], 500);
		}
	}

	/**
	 * Sets up the request environment.
	 *
	 * @param string $method
	 * @param string $uri
	 * @param array $data
	 * @param array $headers
	 * @return void
	 */
	protected function setupRequestEnvironment(string $method, string $uri, array $data, array $headers): void
	{
		$_SERVER['REQUEST_METHOD'] = $method;
		$_SERVER['REQUEST_URI'] = $uri;
		$_SERVER['HTTP_HOST'] = 'localhost';

		// Set headers in $_SERVER
		foreach ($headers as $key => $value)
		{
			$serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
			$_SERVER[$serverKey] = $value;
		}

		// Set request data
		if ($method === 'GET')
		{
			$_GET = $data;
		}
		else
		{
			$_POST = $data;

			// For JSON requests, set raw input
			if (isset($headers['Content-Type']) && $headers['Content-Type'] === 'application/json')
			{
				// Mock php://input for JSON data
				$this->mockPhpInput(json_encode($data));
			}
		}
	}

	/**
	 * Sets up test session.
	 *
	 * @param Request $request
	 * @return void
	 */
	protected function setupTestSession(Request $request): void
	{
		if ($this->authenticatedUser)
		{
			$this->sessionData['user'] = $this->authenticatedUser;
		}

		Session::init();

		// Setup session with test data
		$session = Session::getInstance();
		foreach ($this->sessionData as $key => $value)
		{
			$session->{$key} = $value;
		}
	}

	/**
	 * Executes the request through the router.
	 *
	 * @param Router $router
	 * @param Request $request
	 * @param string $method
	 * @param string $uri
	 * @return mixed
	 */
	protected function executeRequest(Router $router, Request $request, string $method, string $uri): mixed
	{
		// This is a simplified execution - in a real implementation,
		// you would need to integrate with your actual routing system

		// For now, return a mock response
		return [
			'method' => $method,
			'uri' => $uri,
			'data' => $request->all(),
			'message' => 'Test request executed successfully'
		];
	}

	/**
	 * Extracts status code from response.
	 *
	 * @param mixed $result
	 * @return int
	 */
	protected function extractStatusCode(mixed $result): int
	{
		if (is_object($result) && isset($result->code))
		{
			return (int) $result->code;
		}

		if (is_array($result) && isset($result['code']))
		{
			return (int) $result['code'];
		}

		return 200; // Default success
	}

	/**
	 * Gets response headers.
	 *
	 * @return array
	 */
	protected function getResponseHeaders(): array
	{
		// In a real implementation, you would capture actual response headers
		return [];
	}

	/**
	 * Mocks php://input for testing JSON requests.
	 *
	 * @param string $data
	 * @return void
	 */
	protected function mockPhpInput(string $data): void
	{
		// This would need to be implemented based on your framework's
		// request handling mechanism
	}

	/**
	 * Resets HTTP test state.
	 *
	 * @return void
	 */
	protected function resetHttpState(): void
	{
		$this->sessionData = [];
		$this->authenticatedUser = null;
		$this->authToken = null;
		$this->defaultHeaders = [
			'Accept' => 'application/json',
			'Content-Type' => 'application/json'
		];
	}
}