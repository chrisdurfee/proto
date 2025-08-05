<?php declare(strict_types=1);
namespace Proto\Dispatch;

/**
 * ResponseTrait
 *
 * Provides methods to generate response objects with success or error states.
 *
 * @package Proto\Dispatch
 */
trait ResponseTrait
{
	/**
	 * Generate an error response.
	 *
	 * @param string $message The error message.
	 * @return Response The generated error response.
	 */
	protected function error(string $message): Response
	{
		return Response::create(true, $message);
	}

	/**
	 * Generate a response.
	 *
	 * @param bool $error Whether the response indicates an error.
	 * @param string $message The response message.
	 * @param mixed $data Additional response data.
	 * @return Response The generated response object.
	 */
	protected function response(
		bool $error = false,
		string $message = '',
		mixed $data = null
	): Response
	{
		return Response::create($error, $message, $data);
	}
}