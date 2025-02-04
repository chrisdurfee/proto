<?php declare(strict_types=1);
namespace Proto\API;

use Proto\Utils\Sanitize;
use Proto\Http\UploadFile;
use Proto\Http\Request;
use Proto\Http\Session;
use Proto\Http\Session\SessionInterface;
use Proto\Http\Response;
use Proto\Controllers\Response as ControllerResponse;
use Proto\Utils\Format\JsonFormat as Formatter;
use Proto\Http\ServerEvents\ServerEvents;
use Proto\Http\Loop\UpdateEvent;
use Proto\Cache\Cache;
use Proto\Http\RateLimiter;
use Proto\Http\Limit;

/**
 * ApiService
 *
 * This will be the base class for all api services.
 *
 * @package Proto\API
 * @abstract
 */
abstract class ApiService implements ServiceInterface
{
	/**
	 * @var SessionInterface $session
	 */
	protected static SessionInterface $session;

	/**
	 * @var string $cache
	 */
	protected static string $cache;

	/**
	 * @var array $csrfOpenMethods
	 */
	protected $csrfOpenMethods = [];

	/**
	 * This will setup the session.
	 *
	 * @param string $session
	 * @param string $cache
	 * @return void
	 */
	public function __construct(
		string $session = Session::class,
		string $cache = Cache::class
	)
	{
		$this->setupCors();
		static::$session = $session::init();
		static::$cache = $cache;
	}

	/**
	 * This will set up the cors headers.
	 *
	 * @return void
	 */
	public function setupCors(): void
	{
		header('Access-Control-Allow-Origin: *');
	}

	/**
	 * This will check if a method is open to csrf.
	 *
	 * @param string $methodName
	 * @return bool
	 */
	public function isOpenMethod(string $methodName): bool
	{
		return in_array($methodName, $this->csrfOpenMethods);
	}

	/**
	 * This will check if cache is supported.
	 *
	 * @return bool
	 */
	protected function isCacheSupported(): bool
	{
		/**
		 * @var object $cache
		 */
		$cache = self::$cache;
		return $cache::isSupported();
	}

	/**
	 * This will set a value to the session.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	protected function setSession(string $key, $value): void
	{
		$session = self::$session;
		$session->{$key} = $value;
	}

	/**
	 * This will limit the number of requests per minute.
	 *
	 * @param int $maxRequests
	 * @return void
	 */
	protected function limitRequests(int $maxRequests = 100): void
	{
		$limit = Limit::perMinute($maxRequests);
		RateLimiter::check($limit);
	}

	/**
	 * This will set up a server event.
	 *
	 * @param int $interval
	 * @param callable $callback
	 * @return void
	 */
	protected function serverEvent(int $interval, callable $callback): void
	{
		$server = new ServerEvents($interval);
        $server->start(function($loop) use($callback)
        {
            $loop->addEvent(new UpdateEvent($callback));
        });
	}

	/**
	 * This will set up a server event.
	 *
	 * @param callable $callback
	 * @return void
	 */
	protected function eventStream(callable $callback): void
	{
		$server = new ServerEvents();
        $server->stream($callback);
	}

	/**
	 * This will display an error message.
	 *
	 * @param string $code
	 * @return Response
	 */
    protected static function showMessage(string $code = '0'): Response
    {
        $errorMessage = ResponseMessage::getMessage($code);
		return new Response($errorMessage);
	}

	/**
	 * This will create an error response.
	 *
	 * @param string $message
	 * @return object
	 */
	protected function error(string $message = ''): object
	{
		$response = new ControllerResponse();
		$response->error($message);
		return $response->display();
	}

	/**
	 * This will create a response.
	 *
	 * @param mixed $data
	 * @return object
	 */
	protected function response(mixed $data = null): object
	{
		$response = new ControllerResponse($data);
		return $response->display();
	}

	/**
	 * This will clean data.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	protected function clean(mixed $data): mixed
	{
		return Sanitize::clean($data);
	}

	/**
	 * This will get a request input value.
	 *
	 * @param string $key
	 * @param bool $clean
	 * @return mixed
	 */
	protected function input(string $key, bool $clean = true): mixed
	{
		$value = Request::input($key);
		if($clean !== true)
		{
			return $value;
		}

		return $this->clean($value);
	}

	/**
	 * This will check if an input is set.
	 *
	 * @param string $key
	 * @return bool
	 */
	protected function has(string $key): bool
	{
		return Request::has($key);
	}

	/**
	 * This will get a value if set or return the default value.
	 *
	 * @param string $key
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	protected function getValue(string $key, $defaultValue = null): mixed
	{
		$value = Request::has($key) ?? null;
		if (\is_null($value) === false)
		{
			return $value;
		}

		return $defaultValue ?? null;
	}

	/**
	 * This will get a request an int input value.
	 *
	 * @param string $key
	 * @param bool $clean
	 * @return int|null
	 */
	protected function getInt(string $key, bool $clean = true): ?int
	{
		$value = $this->input($key, $clean);
		return (is_null($value))? null : (int)$value;
	}

	/**
	 * This will get a request a bool input value.
	 *
	 * @param string $key
	 * @param bool $clean
	 * @return mixed
	 */
	protected function getBool(string $key, bool $clean = true): bool
	{
		$value = $this->input($key, $clean);
		return (bool)$value;
	}

	/**
	 * This will get a request input json value.
	 *
	 * @param string $key
	 * @return mixed
	 */
	protected function getJson(string $key): mixed
	{
		return Request::json($key);
	}

	/**
	 * This will get the post body.
	 *
	 * @return mixed
	 */
	protected function getPostBody(): mixed
	{
		return Request::body();
	}

	/**
	 * This will decode the data.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	protected function decodeUrl($data): mixed
	{
		return Request::decodeUrl($data);
	}

	/**
	 * This will get the request class.
	 *
	 * @return string
	 */
	protected function getRequest(): string
	{
		return Request::class;
	}

	/**
	 * This will get a request file.
	 *
	 * @param string $name
	 * @return UploadFile|null
	 */
	protected function file(string $name): ?UploadFile
	{
		return Request::file($name);
	}

	/**
	 * This will get a request files.
	 *
	 * @return array
	 */
	protected function files(): array
	{
		return Request::files();
	}

	/**
	 * This will json encode data and echo the result.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public static function encode(mixed $data): mixed
	{
		return Formatter::encode($data);
	}

	/**
	 * This will json decode data.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public static function decode(mixed $data): mixed
	{
		return Formatter::decode($data);
	}
}