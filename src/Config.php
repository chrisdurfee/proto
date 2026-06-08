<?php declare(strict_types=1);
namespace Proto
{
	use Proto\Patterns\Creational\Singleton;
	use Proto\Utils\Format\JsonFormat;
	use Proto\Utils\Files\File;
	use Proto\Database\DatabaseManager;

	/**
	 * Config Class
	 *
	 * Manages application configuration settings using the Singleton pattern.
	 *
	 * @package Proto
	 */
	final class Config extends Singleton
	{
		/**
		 * @var Config $instance The instance of the Config class
		 */
		protected static ?self $instance = null;

		/**
		 * @var object $settings The configuration settings
		 */
		protected object $settings;

		/**
		 * @var string $envUrl Cached URL for the environment
		 */
		private static string $envUrl = '';

		/**
		 * Initializes settings and environment configuration.
		 *
		 * @return void
		 */
		protected function __construct()
		{
			$this->loadSettings();
			$this->detectEnvironment();
			$this->configureErrorReporting();
		}

		/**
		 * Loads settings from the configuration file.
		 *
		 * The parsed config is cached in APCu (when available) keyed by the
		 * file path and modification time. This avoids re-reading and
		 * re-decoding the JSON config on every request in short-lived
		 * processes. Cache is automatically invalidated when the file changes.
		 *
		 * @return void
		 */
		private function loadSettings(): void
		{
			$path = BASE_PATH . '/common/Config/.env';

			$cached = self::loadCachedSettings($path);
			if ($cached !== null)
			{
				$this->settings = $cached;
				return;
			}

			$contents = File::get($path);
			if (!$contents)
			{
				throw new \RuntimeException('Settings file not found.');
			}

			$decodedSettings = JsonFormat::decode($contents);
			if (!$decodedSettings)
			{
				throw new \RuntimeException('Invalid settings file format.');
			}

			$this->settings = $decodedSettings;
			self::storeCachedSettings($path, $decodedSettings);
		}

		/**
		 * Builds the APCu cache key for the config file.
		 *
		 * Includes the file modification time so a changed file produces a
		 * new key, transparently invalidating stale cached settings.
		 *
		 * @param string $path The config file path.
		 * @return string|null The cache key, or null if APCu/file is unavailable.
		 */
		private static function configCacheKey(string $path): ?string
		{
			if (!function_exists('apcu_enabled') || !apcu_enabled())
			{
				return null;
			}

			$mtime = @filemtime($path);
			if ($mtime === false)
			{
				return null;
			}

			return 'proto.config:' . $path . ':' . $mtime;
		}

		/**
		 * Retrieves cached settings from APCu when available.
		 *
		 * @SuppressWarnings PHP0417
		 * @param string $path The config file path.
		 * @return object|null The cached settings, or null on miss.
		 */
		private static function loadCachedSettings(string $path): ?object
		{
			$key = self::configCacheKey($path);
			if ($key === null)
			{
				return null;
			}

			$success = false;
			$cached = apcu_fetch($key, $success);
			return ($success && is_object($cached)) ? $cached : null;
		}

		/**
		 * Stores parsed settings in APCu when available.
		 *
		 * @SuppressWarnings PHP0417
		 * @param string $path The config file path.
		 * @param object $settings The parsed settings to cache.
		 * @return void
		 */
		private static function storeCachedSettings(string $path, object $settings): void
		{
			$key = self::configCacheKey($path);
			if ($key === null)
			{
				return;
			}

			apcu_store($key, $settings, 300);
		}

		/**
		 * Detects the application environment based on the HTTP host.
		 *
		 * @return void
		 */
		private function detectEnvironment(): void
		{
			/**
			 * Sanitize the Host header before comparison. The value is
			 * attacker-controlled, so strip control characters/whitespace
			 * and lowercase it to prevent header-spoofing edge cases.
			 */
			$host = strtolower(trim(preg_replace('/[^A-Za-z0-9\.\-\:]/', '', $_SERVER['HTTP_HOST'] ?? '')));
			$urls = $this->get('domain');

			/**
			 * Fail secure: unknown/spoofed hosts resolve to 'prod' so error
			 * reporting and other dev-only behaviors are never enabled by an
			 * unrecognized Host header. Development hosts must be listed
			 * explicitly under domain.development.
			 */
			$this->set('env', match (true)
			{
				$host === '' || $host === strtolower((string)($urls->production ?? '')) => 'prod',
				isset($urls->staging) && $host === strtolower((string)$urls->staging) => 'staging',
				isset($urls->testing) && $host === strtolower((string)$urls->testing) => 'testing',
				isset($urls->development) && $host === strtolower((string)$urls->development) => 'dev',
				default => 'prod',
			});
		}

		/**
		 * Configures error reporting based on the environment.
		 *
		 * @return void
		 */
		private function configureErrorReporting(): void
		{
			$this->set('errorReporting', $this->get('env') === 'dev');
		}

		/**
		 * Retrieves error reporting status.
		 *
		 * @return bool Whether error reporting is enabled or not
		 */
		public static function errors(): bool
		{
			return static::access('errorReporting');
		}

		/**
		 * Retrieves a configuration value by key.
		 *
		 * @param string $key The configuration key
		 * @return mixed The configuration value
		 */
		public function get(string $key): mixed
		{
			return $this->settings->{$key} ?? null;
		}

		/**
		 * Returns the full configuration object.
		 *
		 * @return object
		 */
		public function getSettings(): object
		{
			return $this->settings;
		}

		/**
		 * This will set a value to the key.
		 *
		 * @param string $key
		 * @param mixed $value
		 * @return self
		 */
		public function set(string $key, $value): self
		{
			if (!isset($key))
			{
				return $this;
			}

			$this->settings->{$key} = $value;
			return $this;
		}

		/**
		 * This will get the value of a key.
		 *
		 * @param string $key
		 * @return mixed
		 */
		public static function access(string $key): mixed
		{
			$config = static::getInstance();
			return $config->get($key);
		}

		/**
		 * This will set a key value.
		 *
		 * @param string $key
		 * @param mixed $value
		 *
		 * @return void
		 */
		public function __set(string $key, mixed $value): void
		{
			$this->set($key, $value);
		}

		/**
		 * This will get a value by key.
		 *
		 * @param string $key
		 * @return mixed
		 */
		public function __get(string $key): mixed
		{
			return $this->get($key);
		}

		/**
		 * Retrieves the current environment.
		 *
		 * @return string|null
		 */
		public function getEnv(): ?string
		{
			return $this->get('env');
		}

		/**
		 * Retrieves the base URL for the current environment.
		 *
		 * @return string
		 */
		public function getUrl(): string
		{
			if (!empty(self::$envUrl))
			{
				return self::$envUrl;
			}

			$urls = $this->get('domain');
			$env = $this->getEnv();
			if ($env === 'prod')
			{
				$url = $urls->production;
			}
			elseif ($env === 'dev')
			{
				$url = $urls->development;
			}
			else
			{
				$url = $urls->{$env} ?? $urls->production;
			}

			// Cache the URL
			self::$envUrl = $url;
			return $url;
		}

		/**
		 * Static method to retrieve the environment URL.
		 *
		 * @return string
		 */
		public static function url(): string
		{
			return self::getInstance()->getUrl();
		}

		/**
		 * Retrieves the database connection settings.
		 *
		 * @param string|null $connection
		 * @return object
		 * @throws \Exception
		 */
		public function getDBSettings(?string $connection = 'default'): object
		{
			$connections = $this->get('connections');
			return DatabaseManager::getDBSettings($connections, $connection, $this->get('env'));
		}
	}
}

namespace
{
	use Proto\Config;

	/**
	 * Sets the config settings to the superglobal env.
	 */
	$_ENV['config'] = Config::getInstance()->getSettings();

	/**
	 * Retrieves a property value from the config.
	 *
	 * @param string $key The property key
	 * @return mixed The property value
	 */
	function env(string $key): mixed
	{
		return Config::access($key);
	}

	/**
	 * Sets a property value in the config.
	 *
	 * @param string $key The property key
	 * @param mixed $value The property value
	 * @return mixed The property value
	 */
	function setEnv(string $key, mixed $value): mixed
	{
		$config = Config::getInstance();
		return $config->set($key, $value);
	}

	/**
	 * Retrieves the base URL for the current environment.
	 *
	 * @return string The base URL
	 */
	function envUrl(): mixed
	{
		return Config::url();
	}

	/**
	 * Retrieves the global Events instance for event-driven functionality.
	 * Supports both local and distributed (Redis) events via prefix-based routing.
	 *
	 * @return \Proto\Events\Events The Events singleton instance
	 */
	function events(): \Proto\Events\Events
	{
		return \Proto\Events\Events::getInstance();
	}

	/**
	 * Sets the base URL for the current environment.
	 */
	define('ENV_URL', envUrl());
}