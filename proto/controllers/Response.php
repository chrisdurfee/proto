<?php declare(strict_types=1);
namespace Proto\Controllers;

/**
 * Response
 *
 * This class generates a structured response object.
 *
 * @package Proto\Controllers
 */
class Response
{
	/**
	 * Indicates whether the response is successful.
	 *
	 * @var bool
	 */
	protected bool $success = true;

	/**
	 * Stores an error message if applicable.
	 *
	 * @var string
	 */
	protected string $message = 'There was an error processing the result.';

	/**
	 * Holds response data.
	 *
	 * @var object|null
	 */
	protected ?object $data = null;

	/**
	 * Initializes a new response object.
	 *
	 * @param mixed $data The response data.
	 */
	public function __construct(mixed $data = null)
	{
		$this->setData($data);
	}

	/**
	 * Sets the response data.
	 *
	 * @param mixed $data The response data.
	 * @return self The current response instance.
	 */
	public function setData(mixed $data = null): self
	{
		if ($data && $data !== true)
		{
			$this->data = is_array($data) ? (object) $data : $data;
		}

		return $this;
	}

	/**
	 * Marks the response as an error.
	 *
	 * @param string $message The error message.
	 * @return self The current response instance.
	 */
	public function error(string $message = ''): self
	{
		$this->success = false;
		if (!empty($message))
		{
			$this->message = $message;
		}
		return $this;
	}

	/**
	 * Creates and returns an error response.
	 *
	 * @param string $message The error message.
	 * @return object The formatted error response.
	 */
	public static function errorResponse(string $message = ''): object
	{
		return (new static())->error($message)->display();
	}

	/**
	 * Returns the structured response object.
	 *
	 * @return object The formatted response.
	 */
	public function display(): object
	{
		$response = $this->data ?? (object) [];
		$response->success = $this->success;
		if (!$this->success)
		{
			$response->message = $this->message;
		}

		return $response;
	}
}