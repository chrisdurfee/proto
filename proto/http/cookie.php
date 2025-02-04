<?php declare(strict_types=1);
namespace Proto\Http;

use Proto\Config;
use Proto\Utils\Filter\Input;

/**
 * Class Cookie
 *
 * Handles cookies.
 *
 * @package Proto\Http
 */
class Cookie
{
	/**
	 * @var string $env
	 */
	protected static $env;

	/**
	 * Cookie constructor.
	 *
	 * @param string $name
	 * @param string $value
	 * @param int $expires
	 * @return void
	 */
	public function __construct(
		protected string $name,
		protected string $value,
		protected int $expires = 0
	)
	{
	}

	/**
	 * Retrieves the name.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Retrieves the value.
	 *
	 * @return string
	 */
	public function getValue(): string
	{
		return $this->value;
	}

	/**
	 * Retrieves the expires.
	 *
	 * @return int
	 */
	public function getExpires(): int
	{
		return $this->expires;
	}

	/**
	 * Sets the cookie.
	 *
	 * @return void
	 */
	public function set(): void
	{
		$options = $this->getOptions();
		setcookie($this->name, $this->value, $options);
	}

	/**
	 * Sets the expire time.
	 *
	 * @param int $expires
	 * @return void
	 */
	public function setExpires(int $expires): void
	{
		$this->expires = $expires;
	}

	/**
	 * Retrieves the config environment.
	 *
	 * @return string
	 */
	protected function getEnv(): string
	{
		if (isset(static::$env))
		{
			return static::$env;
		}

		$config = Config::getInstance();
		return (static::$env = $config->getEnv());
	}

	/**
	 * Retrieves the options.
	 *
	 * @return array
	 */
	public function getOptions(): array
	{
		$env = $this->getEnv();
		$isProd = ($env !== 'dev');

		return [
			"path" => '/',
			"expires" => $this->expires,
			"secure" => $isProd,
			"httponly" => $isProd,
			"samesite" => ($isProd)? 'Strict' : 'Lax'
		];
	}

	/**
	 * Gets a cookie by name.
	 *
	 * @param string $name
	 * @return Cookie|null
	 */
	public static function get(string $name): ?Cookie
	{
		$value = Input::cookie($name);
		if (empty($value) === true)
		{
			return null;
		}

		return new static($name, $value);
	}

	/**
	 * Removes a cookie with the token
	 *
	 * @param string $name
	 * @return void
	 */
	public static function remove(string $name): void
	{
		$cookie = static::get($name);
		if ($cookie)
		{
			$expireDate = time() - (60 * 60 * 24 * 7);
			setcookie($name, "", $expireDate, "/");
		}
	}
}