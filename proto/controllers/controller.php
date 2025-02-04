<?php declare(strict_types=1);
namespace Proto\Controllers;

use Proto\Base;

/**
 * Controller
 *
 * This will allow new controller types to be extended from
 * a base parent controller.
 *
 * This will support returning response objects.
 *
 * @package Proto\Controllers
 * @abstract
 */
abstract class Controller extends Base
{
	/**
	 * This will create an error response.
	 *
	 * @param string $message
	 * @return object
	 */
	protected function error(string $message = ''): object
	{
		$response = new Response();
		$response->error($message);
		return $response->display();
	}

	/**
	 * This will create a response.
	 *
	 * @return object
	 */
	protected function response(): object
	{
		$args = func_get_args();
		$result = $args[0] ?? false;
		if (!$result)
		{
			$message = $args[1] ?? '';
			return $this->error($message);
		}

		$response = new Response();
		$response->setData($result);
		return $response->display();
	}
}