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
class Request extends BaseRequest
{
	/**
	 * Retrieves all request inputs and sanitizes them.
	 *
	 * @return array
	 */
	public static function all(): array
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
	public static function input(string $name, mixed $default = null): mixed
	{
		$input = static::raw($name, $default);
		return static::clean($input);
	}

	/**
	 * Sanitizes input data, including arrays.
	 *
	 * @param mixed $data The data to sanitize.
	 * @return mixed The sanitized data.
	 */
	protected static function clean(mixed $data): mixed
	{
		if (is_array($data))
		{
			return array_map([Sanitize::class, 'clean'], $data);
		}

		return Sanitize::clean($data);
	}
}