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

	/** @var bool Whether the response indicates success */
	public bool $success;

	/** @var mixed Response data payload */
	protected mixed $data = null;

	/** @var bool Whether the response is queued */
	public bool $queued = false;

	/**
	 * Response constructor.
	 *
	 * @param bool $error Whether the response indicates an error.
	 * @param string $message The response message.
	 * @return void
	 */
	public function __construct(
		public bool $error = false,
		public string $message = ''
	)
	{
		$this->success = !$error;
	}

	/**
	 * This will set the response to be queued.
	 *
	 * @return void
	 */
	public function queue(): void
	{
		$this->queued = true;
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