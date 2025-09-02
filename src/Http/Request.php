<?php declare(strict_types=1);
namespace Proto\Http;

use Proto\Config;
use Proto\Utils\Filter\Input;
use Proto\Utils\Filter\Sanitize;
use Proto\Utils\Format\JsonFormat;

/**
 * Class Request
 *
 * Handles the request information processing.
 *
 * @package Proto\Http
 */
class Request
{
	/**
	 * @var string $currentPath Current path of the request
	 */
	protected static string $currentPath;

	/**
	 * @var string|null $ipAddress IP address of the client
	 */
	protected static ?string $ipAddress;

	/**
	 * @var string $currentUrl Full URL of the request
	 */
	protected static string $currentUrl;

	/**
	 * @var string $httpMethod HTTP method of the request
	 */
	protected static string $httpMethod;

	/**
	 * @var string $userAgent User agent of the client
	 */
	protected static string $userAgent;

	/**
	 * @var array $headers Request headers
	 */
	protected static array $headers;

	/**
	 * @var string $mac MAC address of the client
	 */
	protected static string $mac;

	/**
	 * @var mixed $body Request body content
	 */
	protected static mixed $body;

	/**
	 * Get the current path of the request.
	 *
	 * @return string
	 */
	public static function path(): string
	{
		return self::$currentPath ??= Input::server('REQUEST_URI');
	}

	/**
	 * Get the full URL of the request.
	 *
	 * @return string
	 */
	public static function fullUrl(): string
	{
		$host = Input::server('HTTP_HOST');
		$uri = Input::server('REQUEST_URI');

		return self::$currentUrl ??= "//{$host}{$uri}";
	}

	/**
	 * Get the full URL of the request.
	 *
	 * @return string
	 */
	public static function fullUrlWithScheme(): string
	{
		$host = Input::server('HTTP_HOST');
		$uri = Input::server('REQUEST_URI');
		$https = Input::server('HTTPS');
		$scheme = empty($https) ? 'http' : 'https';

		return self::$currentUrl ??= "{$scheme}://{$host}{$uri}";
	}

	/**
	 * Get the IP address of the client.
	 *
	 * @return ?string
	 */
	public static function ip(): ?string
	{
		return self::$ipAddress ??= (PublicIp::get() ?? null);
	}

	/**
	 * Get the HTTP method of the request.
	 *
	 * @return string
	 */
	public static function method(): string
	{
		if (isset(self::$httpMethod))
		{
			return self::$httpMethod;
		}

		self::$httpMethod = Input::server('REQUEST_METHOD');
		self::setCustomInputs(self::$httpMethod);
		return self::$httpMethod;
	}

	/**
	 * This will set the request params as the body
	 * input for PUT and PATCH Requests.
	 *
	 * @param string $method
	 * @return void
	 */
	protected static function setCustomInputs(string $method): void
	{
		if ($method !== 'PUT' && $method !== 'PATCH'  && $method !== 'DELETE')
		{
			return;
		}

		$inputs = static::body();
		if (empty($inputs))
		{
			return;
		}

		parse_str($inputs, $params);
		if (count($params) > 0)
		{
			$_REQUEST = array_merge($_REQUEST, $params);
		}
	}

	/**
	 * This will get the http method.
	 *
	 * @return array
	 */
	public static function headers(): array
	{
		return self::$headers ?? (self::$headers = \array_change_key_case(\getallheaders(), CASE_LOWER));
	}

	/**
	 * This will get a header.
	 *
	 * @param string $header
	 * @return string|null
	 */
	public static function header(string $header): ?string
	{
		$headers = self::headers();
		if (count($headers) < 1)
		{
			return null;
		}

		$row = $headers[$header] ?? null;
		if (!$row)
		{
			return null;
		}

		return Sanitize::string($row);
	}

	/**
	 * This will get the user agent.
	 *
	 * @return string
	 */
	public static function userAgent(): string
	{
		return self::$userAgent ?? (self::$userAgent = Input::server('HTTP_USER_AGENT'));
	}

	/**
	 * This will get the mac address.
	 *
	 * @return string
	 */
	public static function mac(): string
	{
		if (!empty(static::$mac))
		{
			return static::$mac;
		}

		$mac = exec('getmac');
		static::$mac = Sanitize::string(strtok($mac, ' '));
		return static::$mac;
	}

	/**
	 * This will check the http request method.
	 *
	 * @param string $method
	 * @return bool
	 */
	public static function isMethod(string $method = 'GET'): bool
	{
		$httpMethod = self::method();
		return (strtoupper($method) === $httpMethod);
	}

	/**
	 * This will get an input from the request.
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function input(string $name, mixed $default = null)
	{
		return static::sanitized($name, $default);
	}

	/**
	 * This will get an int input from the request.
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return int|null
	 */
	public static function getInt(string $name, mixed $default = null): ?int
	{
		$input = static::input($name, $default);
		return (isset($input))? (int)$input : null;
	}

	/**
	 * This will get a bool input from the request.
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return int|null
	 */
	public static function getBool(string $name, mixed $default = null): ?int
	{
		$input = static::input($name, $default);
		return (isset($input))? (bool)$input : null;
	}

	/**
	 * This will get an item and decode it from json.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public static function json(string $name): mixed
	{
		$item = static::input($name);
		if (!$item)
		{
			return null;
		}

		$item = preg_replace("/\\\\/", "\\\\\\", $item);
		$item = preg_replace("/\\n/", "\\\\n", $item);
		return JsonFormat::decode($item);
	}

	/**
	 * This will get an unfiltered input from the request.
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function sanitized(string $name, mixed $default = null): mixed
	{
		$input = self::raw($name, $default);
		if ($input !== null)
		{
			$input = strip_tags($input);
			$input = trim($input);
			$input = str_replace(['\\\\', '\\\'', '\\"'], ['\\', '\'', ''], $input);
			$input = Sanitize::string($input);
		}
		return $input;
	}

	/**
	 * This will get an unfiltered input from the request.
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function raw(string $name, mixed $default = null): mixed
	{
		$input = $_REQUEST[$name] ?? null;
		if (is_null($input) && !is_null($default))
		{
			$input = $default;
		}
		return $input;
	}

	/**
	 *
	 * @param mixed $data
	 * @return bool
	 */
	protected static function isIterable(mixed $data): bool
	{
		return (is_array($data) || is_object($data));
	}

	/**
	 * This will decode data.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public static function decodeUrl(mixed $data): mixed
	{
		if (self::isIterable($data))
		{
			foreach ($data as &$value)
			{
				if (self::isIterable($value))
				{
					$value = self::decodeUrl($value);
				}
				else
				{
					$value = urldecode((string)$value);
				}
			}
		}
		else
		{
			$data = urldecode((string)$data);
		}

		return $data;
	}

	/**
	 * This will check if the input is set.
	 *
	 * @param string $name
	 * @return bool
	 */
	public static function has(string $name): bool
	{
		return isset($_REQUEST[$name]);
	}

	/**
	 * This will get all the inputs.
	 *
	 * @return array
	 */
	public static function all(): array
	{
		return $_REQUEST;
	}

	/**
	 * This will get the post body.
	 *
	 * @return mixed
	 */
	public static function body(): mixed
	{
		if (isset(static::$body))
		{
			return static::$body;
		}

		$contents = file_get_contents('php://input');
		return (static::$body = ($contents)? $contents : '');
	}

	/**
	 * This will setup an UploadFile.
	 *
	 * @param array|null $file
	 * @return UploadFile|null
	 */
	protected static function setupFile(?array $file = null): ?UploadFile
	{
		if (!$file)
		{
			return null;
		}

		if (self::checkBlockedFile($file) === false)
		{
			return null;
		}

		return new UploadFile($file);
	}

	/**
	 * This will check if the file is allowed.
	 *
	 * @var array
	 */
	protected static array $allowed;

	/**
	 * This will get the allowed file types.
	 *
	 * @return array
	 */
	protected static function getAllowed(): array
	{
		if (isset(self::$allowed))
		{
			return self::$allowed;
		}

		$config = Config::getInstance();
		return (self::$allowed = ($config->supportedFileTypes ?? ['gif', 'csv', 'jpg', 'png', 'webp', 'svg', 'txt', 'pdf']));
	}

	/**
	 * This will get the supported file types.
	 *
	 * @return array
	 */
	public static function getAllowedFileTypes(): array
	{
		return self::getAllowed();
	}

	/**
	 * This will check if a file is allowed by extension.
	 *
	 * @param array $file
	 * @return bool
	 */
	protected static function checkBlockedFile(array $file): bool
	{
		$fileName = $file['name'];
		$ext = \pathinfo($fileName, PATHINFO_EXTENSION);

		return \in_array($ext, self::getAllowed());
	}

	/**
	 * This will get a file.
	 *
	 * @param string $name
	 * @return UploadFile|null
	 */
	public static function file(string $name): ?UploadFile
	{
		$file = $_FILES[$name] ?? null;
		return self::setupFile($file);
	}

	/**
	 * This will get all files.
	 *
	 * @return array
	 */
	public static function files(): array
	{
		$files = $_FILES;
		if (count($files) < 1)
		{
			return [];
		}

		$uploadFiles = [];
		foreach ($files as $key => $value)
		{
			$file = self::setupFile($value);
			if (!$file)
			{
				continue;
			}

			$uploadFiles[$key] = $file;
		}
		return $uploadFiles;
	}
}