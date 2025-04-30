<?php declare(strict_types=1);
namespace Proto\Http\Router;

use Proto\Http\Request as BaseRequest;
use Proto\Utils\Sanitize;

/**
 * Request
 *
 * This class extends the base HTTP Request, adding input sanitization
 * for router-specific requests.
 *
 * @package Proto\Http\Router
 */
class Request
{
	/**
	 * This will get the properties from the base request.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get(string $name): mixed
	{
		return BaseRequest::${$name};
	}

	/**
	 * This will call the base request methods.
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call(string $name, array $arguments): mixed
	{
		return BaseRequest::{$name}(...$arguments);
	}

	/**
	 * Retrieves all request inputs and sanitizes them.
	 *
	 * @return array
	 */
	public function all(): array
	{
		return static::clean($_REQUEST ?? []);
	}

	/**
	 * Retrieves a specific input from the request, with sanitization.
	 *
	 * @param string $name The input key to retrieve.
	 * @param mixed $default The default value if input is not found.
	 * @return mixed The sanitized input value.
	 */
	public function input(string $name, mixed $default = null): mixed
	{
		$input = BaseRequest::raw($name, $default);
		return $this->clean($input);
	}

	/**
	 * Sanitizes input data, including arrays.
	 *
	 * @param mixed $data The data to sanitize.
	 * @return mixed The sanitized data.
	 */
	protected function clean(mixed $data): mixed
	{
		if (is_array($data))
		{
			return array_map([Sanitize::class, 'clean'], $data);
		}

		return Sanitize::clean($data);
	}

	/**
	 * This will get an item and decode it from json.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function json(string $name): mixed
	{
		$item = $this->input($name);
		if (!$item)
		{
			return null;
		}

		return BaseRequest::json($item);
	}
}