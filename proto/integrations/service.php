<?php declare(strict_types=1);
namespace Proto\Integrations;

use Proto\Utils\Format\JsonFormat;
use Proto\Controllers\Response;
use Proto\Config;
use Proto\Tests\Debug;

/**
 * Service
 *
 * This will setup a service to be used by the integrations.
 *
 * @package Proto\Integrations
 * @abstract
 */
abstract class Service
{
	/**
	 * @var string $url
	 */
	protected $url = '';

	public function __construct()
	{

	}

	/**
	 * This will create an error response.
	 *
	 * @param string $message
	 * @param mixed $data
	 * @return object
	 */
	protected function error(string $message = '', mixed $data = null): object
	{
		if (Config::errors())
		{
			Debug::render($data);
		}

		$response = new Response();
		$response->error($message);
		$response->setData($data);
		return $response->display();
	}

	/**
	 * This will create a response.
	 *
	 * @return object|null
	 */
	protected function response(): ?object
	{
		$args = func_get_args();
		$result = $args[0] ?? false;
		if (!$result)
		{
			$message = $args[1] ?? '';
			$this->error($message);
			return null;
		}

		$response = new Response();
		$response->setData($result);
		return $response->display();
	}

	/**
	 * This will json encode data.
	 *
	 * @param mixed $data
	 * @return string|null
	 */
	public static function encode(mixed $data): ?string
	{
		return JsonFormat::encode($data);
	}

	/**
	 * This will json decode data.
	 *
	 * @param mixed $data
	 * @return mixed
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