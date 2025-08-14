<?php declare(strict_types=1);
namespace Proto\Controllers;

use Proto\Base;

/**
 * Controller
 *
 * This class serves as the base for all controllers, allowing new
 * controller types to extend from a common parent.
 *
 * It supports returning response objects for standardized output.
 *
 * @package Proto\Controllers
 * @abstract
 */
abstract class Controller extends Base implements ControllerInterface
{
	/**
	 * @var string|null $policy
	 */
	protected ?string $policy = null;

	/**
	 * This will get the policy for the controller.
	 *
	 * @return string|null
	 */
	public function getPolicy(): ?string
	{
		return $this->policy;
	}

	/**
	 * Generates an error response.
	 *
	 * @param string $message The error message.
	 * @param int $statusCode The HTTP status code.
	 * @return object The error response object.
	 */
	protected function error(string $message = '', int $statusCode = 200): object
	{
		$response = new Response();
		$response->error($message);
		$response->setData([
			'code' => $statusCode,
		]);
		return $response->format();
	}

	/**
	 * Generates a success response.
	 *
	 * @param object|array $data The response data.
	 * @param int $statusCode The HTTP status code.
	 * @return object The success response object.
	 */
	protected function success(object|array $data, int $statusCode = 200): object
	{
		$response = new Response();
		$data = is_array($data) ? (object) $data : $data;

		if ($statusCode)
		{
			$data->code = $statusCode;
		}

		$response->success($data);
		return $response->format();
	}

	/**
	 * Generates a response based on the provided arguments.
	 *
	 * @param mixed ...$args Response data and optional error message.
	 * @return object The formatted response object.
	 */
	protected function response(mixed ...$args): object
	{
		$result = $args[0] ?? false;
		if (!$result)
		{
			return $this->error($args[1] ?? '');
		}

		$response = new Response();
		$response->setData($result);
		return $response->format();
	}
}