<?php declare(strict_types=1);
namespace Proto\Tests;

use Proto\Http\Response;
use \PHPUnit\Framework\Assert;

/**
 * TestResponse
 *
 * Wraps HTTP responses for testing with fluent assertion methods.
 *
 * @package Proto\Tests
 */
class TestResponse
{
	/**
	 * @var Response $response
	 */
	protected Response $response;

	/**
	 * @var mixed $content
	 */
	protected mixed $content;

	/**
	 * @var int $statusCode
	 */
	protected int $statusCode;

	/**
	 * @var array $headers
	 */
	protected array $headers;

	/**
	 * Constructor.
	 *
	 * @param Response $response
	 * @param mixed $content
	 * @param int $statusCode
	 * @param array $headers
	 */
	public function __construct(Response $response, mixed $content, int $statusCode = 200, array $headers = [])
	{
		$this->response = $response;
		$this->content = $content;
		$this->statusCode = $statusCode;
		$this->headers = $headers;
	}

	/**
	 * Asserts the response status code.
	 *
	 * @param int $status
	 * @return self
	 */
	public function assertStatus(int $status): self
	{
		Assert::assertEquals($status, $this->statusCode,
			"Expected status code [{$status}] but received [{$this->statusCode}]"
		);
		return $this;
	}

	/**
	 * Asserts the response is successful (2xx status codes).
	 *
	 * @return self
	 */
	public function assertSuccessful(): self
	{
		Assert::assertTrue($this->statusCode >= 200 && $this->statusCode < 300,
			"Expected successful status code but received [{$this->statusCode}]"
		);
		return $this;
	}

	/**
	 * Asserts the response JSON matches expected data.
	 *
	 * @param array $data
	 * @return self
	 */
	public function assertJson(array $data): self
	{
		$responseData = $this->getJsonData();

		foreach ($data as $key => $value)
		{
			Assert::assertEquals($value, $responseData[$key] ?? null,
				"Failed asserting that JSON response contains [{$key}] with value [{$value}]"
			);
		}
		return $this;
	}

	/**
	 * Asserts the response JSON has the expected structure.
	 *
	 * @param array $structure
	 * @return self
	 */
	public function assertJsonStructure(array $structure): self
	{
		$responseData = $this->getJsonData();
		$this->assertJsonStructureRecursive($structure, $responseData);
		return $this;
	}

	/**
	 * Asserts the response JSON contains a specific fragment.
	 *
	 * @param array $data
	 * @return self
	 */
	public function assertJsonFragment(array $data): self
	{
		$responseData = $this->getJsonData();
		$this->assertArrayContainsFragment($data, $responseData);
		return $this;
	}

	/**
	 * Asserts the response JSON does not contain specific data.
	 *
	 * @param array $data
	 * @return self
	 */
	public function assertJsonMissing(array $data): self
	{
		$responseData = $this->getJsonData();

		foreach ($data as $key => $value)
		{
			Assert::assertNotEquals($value, $responseData[$key] ?? null,
				"Failed asserting that JSON response does not contain [{$key}] with value [{$value}]"
			);
		}
		return $this;
	}

	/**
	 * Asserts the response is a redirect.
	 *
	 * @param string|null $uri
	 * @return self
	 */
	public function assertRedirect(?string $uri = null): self
	{
		Assert::assertTrue($this->statusCode >= 300 && $this->statusCode < 400,
			"Expected redirect status code but received [{$this->statusCode}]"
		);

		if ($uri !== null)
		{
			$location = $this->headers['Location'] ?? null;
			Assert::assertEquals($uri, $location,
				"Expected redirect to [{$uri}] but got [{$location}]"
			);
		}
		return $this;
	}

	/**
	 * Gets the response content.
	 *
	 * @return mixed
	 */
	public function getContent(): mixed
	{
		return $this->content;
	}

	/**
	 * Gets the response status code.
	 *
	 * @return int
	 */
	public function getStatusCode(): int
	{
		return $this->statusCode;
	}

	/**
	 * Gets the response headers.
	 *
	 * @return array
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * Gets the JSON decoded response data.
	 *
	 * @return array
	 */
	protected function getJsonData(): array
	{
		if (is_string($this->content))
		{
			$decoded = json_decode($this->content, true);
			return is_array($decoded) ? $decoded : [];
		}

		if (is_object($this->content))
		{
			return json_decode(json_encode($this->content), true) ?: [];
		}

		return is_array($this->content) ? $this->content : [];
	}

	/**
	 * Recursively asserts JSON structure.
	 *
	 * @param array $structure
	 * @param array $data
	 * @return void
	 */
	protected function assertJsonStructureRecursive(array $structure, array $data): void
	{
		foreach ($structure as $key => $value)
		{
			if (is_array($value))
			{
				Assert::assertArrayHasKey($key, $data,
					"Failed asserting that JSON structure has key [{$key}]"
				);
				$this->assertJsonStructureRecursive($value, $data[$key]);
			}
			else
			{
				Assert::assertArrayHasKey($value, $data,
					"Failed asserting that JSON structure has key [{$value}]"
				);
			}
		}
	}

	/**
	 * Asserts that an array contains a fragment.
	 *
	 * @param array $fragment
	 * @param array $data
	 * @return void
	 */
	protected function assertArrayContainsFragment(array $fragment, array $data): void
	{
		foreach ($fragment as $key => $value)
		{
			if (is_array($value))
			{
				Assert::assertArrayHasKey($key, $data);
				$this->assertArrayContainsFragment($value, $data[$key]);
			}
			else
			{
				Assert::assertEquals($value, $data[$key] ?? null,
					"Failed asserting that response contains [{$key}] with value [{$value}]"
				);
			}
		}
	}
}