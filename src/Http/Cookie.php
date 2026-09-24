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
		$options = $this->getOptions();
		setcookie($this->name, $this->value, $options);

		// A host-only cookie with the same name outranks the parent-domain
		// cookie. Drop it so the shared session is the one the browser sends.
		if (isset($options['domain']))
		{
			unset($options['domain']);
			$options['expires'] = 1;
			setcookie($this->name, '', $options);
		}
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

		$options = [
			'expires' => $this->expires,
			'path' => '/',
			'secure' => $isProd, // Secure flag enabled for HTTPS in production
			'httponly' => true, // Prevents JavaScript access
			'samesite' => $isProd ? 'Strict' : 'Lax'
		];

		$domain = self::resolveSharedDomain(
			self::requestHost(),
			self::domainWideEnabled(),
			self::configuredParent()
		);
		if ($domain !== null)
		{
			$options['domain'] = $domain;
		}

		return $options;
	}

	/**
	 * Parent-domain cookie scope when `cookieDomainWide` is on.
	 *
	 * Returns `.example.com` only for a production host that is the
	 * configured parent or one of its subdomains. Localhost, IP hosts,
	 * and every non-production env stay host-only.
	 *
	 * @param string $host Request host, without a port.
	 * @param bool $enabled `cookieDomainWide` from config.
	 * @param string|null $parent Registrable parent, such as `example.com`.
	 * @return string|null Leading-dot domain, or null to stay host-only.
	 */
	public static function resolveSharedDomain(string $host, bool $enabled, ?string $parent): ?string
	{
		if (!$enabled)
		{
			return null;
		}

		$host = self::normalizeHost($host);
		$parent = self::normalizeHost((string)$parent);
		if ($host === '' || $parent === '' || !str_contains($parent, '.'))
		{
			return null;
		}

		if (filter_var($host, FILTER_VALIDATE_IP) || filter_var($parent, FILTER_VALIDATE_IP))
		{
			return null;
		}

		if ($host !== $parent && !str_ends_with($host, '.' . $parent))
		{
			return null;
		}

		return '.' . $parent;
	}

	/**
	 * @return bool
	 */
	protected static function domainWideEnabled(): bool
	{
		$flag = env('cookieDomainWide');
		return $flag === true || $flag === 1 || $flag === '1' || $flag === 'true';
	}

	/**
	 * Production parent only. Staging and dev stay host-only so a
	 * staging host under the same registrable domain cannot mint a
	 * cookie the production apps will accept.
	 *
	 * @return string|null
	 */
	protected static function configuredParent(): ?string
	{
		if (Config::getInstance()->getEnv() !== 'prod')
		{
			return null;
		}

		$domain = env('domain');
		if (!is_object($domain))
		{
			return null;
		}

		$parent = trim((string)($domain->production ?? ''));
		$parent = preg_replace('#^https?://#i', '', $parent) ?? $parent;
		$parent = explode('/', $parent, 2)[0];

		return $parent !== '' ? $parent : null;
	}

	/**
	 * @return string
	 */
	protected static function requestHost(): string
	{
		$host = $_SERVER['HTTP_HOST'] ?? '';
		return is_string($host) ? $host : '';
	}

	/**
	 * @param string $host
	 * @return string
	 */
	protected static function normalizeHost(string $host): string
	{
		$host = strtolower(trim($host));
		$host = explode(':', $host, 2)[0];
		return rtrim($host, '.');
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
		$opts = (new static($name, '', 1))->getOptions();
		setcookie($name, '', $opts);
		if (isset($opts['domain']))
		{
			unset($opts['domain']);
			setcookie($name, '', $opts);
		}
	}
}