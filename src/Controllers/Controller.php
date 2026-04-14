<?php declare(strict_types=1);
namespace Proto\Controllers;

use Proto\Base;
use Proto\Http\Router\Request;

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
	 * The item key used in requests.
	 *
	 * @var string
	 */
	protected string $item = 'item';

	/**
	 * Retrieves the request item from the request object.
	 *
	 * @param Request $request The request object.
	 * @return object The request item.
	 */
	public function getRequestItem(Request $request): object
	{
		return $request->json($this->item) ?? (object) $request->all();
	}

	/**
	 * Generates an error response.
	 *
	 * @param string $message The error message.
	 * @param int $statusCode The HTTP status code.
	 * @param array<string, string[]> $errors Optional field-level validation errors.
	 * @return object The error response object.
	 */
	protected function error(string $message = '', int $statusCode = 200, array $errors = []): object
	{
		$response = new Response();
		$response->error($message);
		$data = ['code' => $statusCode];
		if (!empty($errors))
		{
			$data['errors'] = $errors;
		}
		$response->setData($data);
		return $response->format();
	}

	/**
	 * Generates a success response.
	 *
	 * Data properties are spread directly onto the response envelope and
	 * 'success: true' is always included at the top level.
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

		$data = is_array($data) ? (object) $data : $data;
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
	 * Data properties (object or array) are spread directly onto the response
	 * envelope and 'success: true' is always included at the top level.
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