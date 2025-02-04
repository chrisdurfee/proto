<?php declare(strict_types=1);
namespace Proto\Controllers;

/**
 * Response
 *
 * This will create a response object.
 *
 * @package Proto\Controllers
 */
class Response
{
	/**
	 * @var bool $success
	 */
	protected bool $success = true;

	/**
	 * @var string $message
	 */
	protected string $message = 'There was an error processing the result.';

	/**
	 * @var object $data
	 */
	protected $data;

	/**
	 * This will create a new response object.
	 *
	 * @param mixed $data
	 * @return void
	 */
	public function __construct(mixed $data = null)
	{
		$this->setData($data);
	}

	/**
	 * This will set the response data.
	 *
	 * @param mixed $data
	 * @return self
	 */
	public function setData(mixed $data = null): self
	{
		if (!$data || $data === true)
		{
			return $this;
		}

		if (is_array($data))
		{
			$data = (object)$data;
		}
		$this->data = $data;
		return $this;
	}

	/**
	 * This will set the response as an error.
	 *
	 * @param string $message
	 * @return self
	 */
	public function error(string $message = ''): self
	{
		$this->success = false;

		if (empty($message) !== true)
		{
			$this->message = $message;
		}
		return $this;
	}

	/**
	 * This will create an error response.
	 *
	 * @param string $message
	 * @return object
	 */
	public static function errorResponse(string $message = ''): object
	{
		$response = new static();
		$response->error($message);
		return $response->display();
	}

	/**
	 * This will get the response data.
	 *
	 * @return object
	 */
	public function display(): object
	{
		$data = is_object($this->data)? $this->data : (object)[];
		$data->success = $this->success;

		if ($data->success !== true)
		{
			$data->message = $this->message;
		}

		return $data;
	}
}