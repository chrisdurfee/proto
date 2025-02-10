<?php declare(strict_types=1);
namespace Proto\Dispatch;

/**
 * Class Response
 *
 * Creates a response object to standardize API or system responses.
 *
 * @package Proto\Dispatch
 */
class Response
{
	/** @var bool Whether the response has been sent */
	public bool $sent = false;

	/** @var bool Whether the response indicates an error */
	public bool $error;

	/** @var bool Whether the response indicates success */
	public bool $success;

	/** @var string Response message */
	public string $message;

	/** @var mixed Response data payload */
	protected mixed $data = null;

	/**
	 * Response constructor.
	 *
	 * @param bool $error Whether the response indicates an error.
	 * @param string $message The response message.
	 */
	public function __construct(bool $error = false, string $message = '')
	{
		$this->error = $error;
		$this->success = !$error;
		$this->message = $message;
	}

	/**
	 * Factory method to create a response instance.
	 *
	 * @param bool $error Whether the response indicates an error.
	 * @param string $message The response message.
	 * @param mixed $data Optional additional data.
	 * @return Response The created response instance.
	 */
	public static function create(bool $error = false, string $message = '', mixed $data = null): Response
	{
		$response = new self($error, $message);
		$response->sent = !$error;

		if ($data !== null)
        {
			$response->setData($data);
		}

		return $response;
	}

	/**
	 * Sets the response data.
	 *
	 * @param mixed $data The response data.
	 * @return void
	 */
	public function setData(mixed $data): void
	{
		$this->data = $data;
	}

	/**
	 * Retrieves the response data.
	 *
	 * @return mixed The stored response data.
	 */
	public function getData(): mixed
	{
		return $this->data;
	}
}