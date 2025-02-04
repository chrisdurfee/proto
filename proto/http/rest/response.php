<?php declare(strict_types=1);
namespace Proto\Http\Rest;

use Proto\Utils\Format\JsonFormat;

/**
 * Response
 *
 * This will handle the response.
 *
 * @package Proto\Http\Rest
 */
class Response
{
    /**
     * This will create the response.
     *
     * @param int $code
     * @param mixed $data
     * @param bool $json
     * @return void
     */
	public function __construct(
        public readonly int $code,
        public mixed $data,
        public readonly bool $json = true
    )
    {
        $this->data = $this->data($data);
    }

    /**
     * This will set the response data.
     *
     * @param mixed $data
     * @return mixed
     */
    protected function data(mixed $data): mixed
    {
        // this will decode the json data
        return ($this->json === true && !empty($data))? JsonFormat::decode($data) : $data;
    }
}