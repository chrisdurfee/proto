<?php declare(strict_types=1);
namespace Proto\Http;

use Proto\Config;
use Proto\Utils\Filter\Input;

/**
 * Class Cookie
 *
 * Handles cookies securely and efficiently.
 *
 * @package Proto\Http
 */
class Cookie
{
	/**
	 * @var string|null $env Stores the environment setting.
	 */
	protected static ?string $env = null;

	/**
	 * Constructs a Cookie instance.
	 *
	 * @param string $name Cookie name.
	 * @param string $value Cookie value.
	 * @param int $expires Expiration timestamp (default: 0).
	 */
	public function __construct(
		protected string $name,
		protected string $value,
		protected int $expires = 0
	)
	{
	}

	/**
	 * Retrieves the cookie name.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Retrieves the cookie value.
	 *
	 * @return string
	 */
	public function getValue(): string
	{
		return $this->value;
	}

	/**
	 * Retrieves the expiration timestamp.
	 *
	 * @return int
	 */
	public function getExpires(): int
	{
		return $this->expires;
	}

	/**
	 * Sets the cookie with appropriate options.
	 *
	 * @return void
	 */
	public function set(): void
	{
		setcookie($this->name, $this->value, $this->getOptions());
	}

	/**
	 * Sets the expiration timestamp.
	 *
	 * @param int $expires Expiration timestamp.
	 * @return void
	 */
	public function setExpires(int $expires): void
	{
		$this->expires = $expires;
	}

	/**
	 * Retrieves the environment configuration.
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
		return static::$env = $config->getEnv();
	}

	/**
	 * Retrieves cookie security options.
	 *
	 * @return array
	 */
	protected function getOptions(): array
	{
		$isProd = ($this->getEnv() !== 'dev');

		return [
			'expires' => $this->expires,
			'path' => '/',
			'secure' => $isProd, // Secure flag enabled for HTTPS in production
			'httponly' => true, // Prevents JavaScript access
			'samesite' => $isProd ? 'Strict' : 'Lax'
		];
	}

	/**
	 * Retrieves a cookie by name.
	 *
	 * @param string $name Cookie name.
	 * @return Cookie|null Returns a Cookie object if found, otherwise null.
	 */
	public static function get(string $name): ?self
	{
		$value = Input::cookie($name);
		return $value !== '' ? new static($name, $value) : null;
	}

	/**
	 * Removes a cookie by setting it to expire in the past.
	 *
	 * @param string $name Cookie name.
	 * @return void
	 */
	public static function remove(string $name): void
	{
		setcookie($name, '', [
			'expires' => time() - 604800, // 1 week in the past
			'path' => '/',
			'secure' => true,
			'httponly' => true,
			'samesite' => 'Strict'
		]);
	}
}