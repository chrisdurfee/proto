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
	 * Objects are automatically wrapped under a 'row' key so that 'success'
	 * is always a top-level sibling rather than merged into domain data.
	 *
	 * @param object|array|null $data The response data.
	 * @param int $statusCode The HTTP status code.
	 * @return object The success response object.
	 */
	protected function success(object|array|null $data = null, int $statusCode = 200): object
	{
		if ($data === null)
		{
			$data = [];
		}

		if (is_object($data))
		{
			$data = ['row' => $data];
		}

		$data = (object) $data;
		if ($statusCode)
		{
			$data->code = $statusCode;
		}

		$response = new Response($data);
		return $response->format();
	}

	/**
	 * Generates a response based on the provided arguments.
	 *
	 * Objects are automatically wrapped under a 'row' key so that 'success'
	 * is always a top-level sibling rather than merged into domain data.
	 * Pass an associative array (e.g. ['rows' => $data]) to spread multiple
	 * keys directly onto the response envelope.
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

		if (is_object($result))
		{
			$result = ['row' => $result];
		}

		$response = new Response();
		$response->setData($result);
		return $response->format();
	}
}