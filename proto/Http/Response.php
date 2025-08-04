<?php declare(strict_types=1);
namespace Proto\Http;

use Proto\Utils\Format\JsonFormat as Formatter;

/**
 * Class Response
 *
 * Handles HTTP responses by setting the response code and rendering data as JSON.
 *
 * @package Proto\Http
 */
class Response
{
	/**
	 * The HTTP response code.
	 */
	protected int $code;

	/**
	 * The response data.
	 */
	protected ?object $data = null;

	/**
	 * Initializes the response and renders the output.
	 *
	 * @param mixed $data The response data.
	 * @param int $code The HTTP status code.
	 */
	public function __construct(mixed $data = null, int $code = 200)
	{
		$this->setCode($code);
		$this->setData($data);
		$this->send();
	}

	/**
	 * Sets the HTTP response code.
	 *
	 * @param int $code
	 * @return void
	 */
	protected function setCode(int $code): void
	{
		$this->code = $code;
		http_response_code($code);
	}

	/**
	 * Sets the response data.
	 *
	 * @param mixed $data
	 * @return void
	 */
	protected function setData(mixed $data = null): void
	{
		if (!empty($data))
		{
			$this->data = is_array($data) ? (object)$data : $data;
		}
	}

	/**
	 * Sends the JSON response.
	 *
	 * @return void
	 */
	protected function send(): void
	{
		if (is_null($this->data))
		{
			return;
		}

		header('Content-Type: application/json');
		Formatter::encodeAndRender($this->data);
	}

	/**
	 * Creates and sends a success response.
	 *
	 * @param array|object|null $data
	 * @param int $code
	 * @return void
	 */
	public static function success(mixed $data = null, int $code = 200): void
	{
		new self($data, $code);
	}

	/**
	 * Creates and sends an error response.
	 *
	 * @param string $message
	 * @param int $code
	 * @return void
	 */
	public static function error(string $message, int $code = 400): void
	{
		new self(['error' => $message], $code);
	}
}