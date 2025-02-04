<?php declare(strict_types=1);
namespace Proto\Http;

use Proto\Utils\Format\JsonFormat as Formatter;

/**
 * Response
 *
 * This will setup an object to set the http response code
 * and render the response data in json.
 *
 * @package Proto\Http
 */
class Response
{
	/**
	 * The response code.
	 *
	 * @var int $code
	 */
	protected int $code;

	/**
	 * The response data.
	 *
	 * @var object $data
	 */
	protected object $data;

	/**
	 * This will setup the response and render to the
	 * screen.
	 *
	 * @param array|object $data
	 * @param int $code
	 * @return void
	 */
	public function __construct(mixed $data, int $code = 200)
	{
		$this->setCode($code);
		$this->setData($data);
		$this->encode();
	}

	/**
	 * This will set the response code.
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
	 * This will set the data.
	 *
	 * @param array|object|null $data
	 * @return void
	 */
	public function setData(mixed $data = null)
	{
		if (!$data)
		{
			return;
		}

		if (is_array($data))
		{
			$data = (object)$data;
		}
		$this->data = $data;
	}

	/**
	 * This will enocde the data to json.
	 *
	 * @return void
	 */
	public function encode(): void
	{
		$data = $this->data;
		if (!is_object($data))
		{
			return;
		}

		header('Content-Type: application/json');
		Formatter::encodeAndRender($data);
	}
}