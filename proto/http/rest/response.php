<?php declare(strict_types=1);
namespace Proto\Http\Rest;

use Proto\Utils\Format\JsonFormat;

/**
 * Response
 *
 * Handles API responses, supporting JSON decoding.
 *
 * @package Proto\Http\Rest
 */
class Response
{
	/**
	 * Initializes the response.
	 *
	 * @param int $code HTTP status code.
	 * @param mixed $data Response data.
	 * @param bool $json Whether the data is in JSON format.
	 */
	public function __construct(
		public readonly int $code,
		public mixed $data,
		public readonly bool $json = true
	)
	{
		$this->data = $this->processData($data);
	}

	/**
	 * Processes the response data.
	 *
	 * @param mixed $data Response data.
	 * @return mixed Processed data.
	 */
	protected function processData(mixed $data): mixed
	{
		if (!$this->json || empty($data))
		{
			return $data;
		}

		return JsonFormat::decode($data);
	}
}