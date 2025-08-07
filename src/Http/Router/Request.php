<?php declare(strict_types=1);
namespace Proto\Http\Router;

use Proto\Http\Request as BaseRequest;
use Proto\Utils\Sanitize;
use Proto\Utils\Format\JsonFormat;
use Proto\Api\Validator;

/**
 * Request
 *
 * This class extends the base HTTP Request, adding input sanitization
 * for router-specific requests.
 *
 * @mixin BaseRequest
 * @package Proto\Http\Router
 */
class Request
{
	/**
	 * Constructor to initialize the request parameters.
	 *
	 * @param object|null $params The request parameters.
	 * @return void
	 */
	public function __construct(
		protected ?object $params = null
	)
	{

	}

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
	 * This will set the params for the request.
	 *
	 * @param object $params
	 * @return void
	 */
	public function setParams(object $params): void
	{
		$this->params = $params;
	}

	/**
	 * This will get the params from the request.
	 *
	 * @return ?object
	 */
	public function params(): ?object
	{
		return $this->params;
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
	 * Gets the validated data from the request.
	 *
	 * @param array $rules
	 * @param string|null $keyName
	 * @return ?object
	 */
	public function validate(array $rules = [], ?string $keyName = 'item'): ?object
	{
		$data = $this->json($keyName) ?? (object)$this->all();
		if (!$data)
		{
			return null;
		}

		if (count($rules) < 1)
		{
			return $data;
		}

		$validator = Validator::create($data, $rules);
		if (!$validator->isValid())
		{
			$statusCode = 404;
			$response = new Response();
			$response->sendHeaders($statusCode)->json([
				"message"=> $validator->getMessage(),
				"success"=> false
			]);
			die;
		}

		return $data;
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

		$item = preg_replace("/\\\\/", "\\\\\\", $item);
		$item = preg_replace("/\\n/", "\\\\n", $item);
		return JsonFormat::decode($item);
	}
}