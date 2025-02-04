<?php declare(strict_types=1);
namespace Proto\Http\Router;

use Proto\Http\Request as BaseRequest;
use Proto\Utils\Sanitize;

/**
 * Request
 *
 * This will handle the request.
 *
 * @package Proto\Http\Router
 */
class Request extends BaseRequest
{
    /**
	 * This will get all the inputs.
	 *
	 * @return array
	 */
	public static function all(): array
	{
		return static::clean($_REQUEST);
	}

    /**
	 * This will get an input from the request.
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function input(string $name, mixed $default = null): mixed
	{
		$input = static::raw($name, $default);
		return static::clean($input);
	}

	/**
	 * This will clean data.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	protected static function clean(mixed $data): mixed
	{
		return Sanitize::clean($data);
	}
}