<?php declare(strict_types=1);
namespace Common\Services\Traits;

use Proto\Controllers\Response;

/**
 * ResponseTrait
 *
 * This trait can be used for controller response methods.
 *
 * @package Common\Services\Traits
 */
trait ResponseTrait
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
		return $response->format();
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
		return $response->format();
	}
}