<?php declare(strict_types=1);
namespace Proto\Integrations;

use Proto\Utils\Format\JsonFormat;
use Proto\Controllers\Response;
use Proto\Config;
use Proto\Tests\Debug;

/**
 * Class Service
 *
 * This will setup a service to be used by the integrations.
 *
 * @package Proto\Integrations
 * @abstract
 */
abstract class Service
{
	/**
	 * URL
	 *
	 * @var string
	 */
	protected string $url = '';

	/**
	 * Creates an error response.
	 *
	 * @param string $message The error message.
	 * @param mixed $data Additional data for the error.
	 * @return object The error response object.
	 */
	public static function error(string $message = '', mixed $data = null): object
	{
		if (Config::errors())
		{
			Debug::render($data);
		}

		$response = new Response();
		$response->error($message);
		$response->setData($data);
		return $response->format();
	}

	/**
	 * Creates a response.
	 *
	 * @param mixed ...$args The response data.
	 * @return object|null The response object or null if there's an error.
	 */
	public static function response(...$args): ?object
	{
		$result = $args[0] ?? false;
		if (!$result)
		{
			$message = $args[1] ?? '';
			static::error($message);
			return null;
		}

		$response = new Response();
		$response->setData($result);
		return $response->format();
	}

	/**
	 * JSON encodes data.
	 *
	 * @param mixed $data The data to encode.
	 * @return string|null The JSON encoded string or null on failure.
	 */
	public static function encode(mixed $data): ?string
	{
		return JsonFormat::encode($data);
	}

	/**
	 * JSON decodes data.
	 *
	 * @param mixed $data The JSON string to decode.
	 * @return mixed The decoded data.
	 */
	public static function decode(mixed $data): mixed
	{
		if ($data === '')
		{
			return null;
		}

		return JsonFormat::decode($data);
	}
}

namespace
{
	/**
	 * Encodes data.
	 *
	 * @param mixed $data The data to encode.
	 * @return string|null The encoded data.
	 */
	function encode(mixed $data): ?string
	{
		return Proto\Integrations\Service::encode($data);
	}

	/**
	 * Decodes data.
	 *
	 * @param mixed $data The data to decode.
	 * @return mixed The decoded data.
	 */
	function decode(mixed $data): mixed
	{
		return Proto\Integrations\Service::decode($data);
	}

	/**
	 * Creates a response.
	 *
	 * @param mixed ...$args The response data.
	 * @return object|null The response object or null if there's an error.
	 */
	function response(...$args): ?object
	{
		return Proto\Integrations\Service::response(...$args);
	}

	/**
	 * Creates an error response.
	 *
	 * @param string $message The error message.
	 * @param mixed $data Additional data for the error.
	 * @return object The error response object.
	 */
	function errorResponse(string $message = '', mixed $data = null): object
	{
		return Proto\Integrations\Service::error($message, $data);
	}
}