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
abstract class Controller extends Base
{
	/**
	 * Generates an error response.
	 *
	 * @param string $message The error message.
	 * @return object The error response object.
	 */
	protected function error(string $message = ''): object
	{
		$response = new Response();
		$response->error($message);
		return $response->display();
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
		return $response->display();
	}
}