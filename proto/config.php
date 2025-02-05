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
		 * @return void
		 */
		private function loadSettings(): void
		{
			$contents = File::get(__DIR__ . '/../app/config/.env');

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
		}

		/**
		 * Detects the application environment based on the HTTP host.
		 *
		 * @return void
		 */
		private function detectEnvironment(): void
		{
			$host = $_SERVER['HTTP_HOST'] ?? '';
			$urls = $this->get('urls');

			$this->set('env', match (true)
			{
				$host === '' || $host === $this->get('baseUrl') => 'prod',
				isset($urls->staging) && $host === $urls->staging => 'staging',
				isset($urls->testing) && $host === $urls->testing => 'testing',
				default => 'dev',
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

			$urls = $this->get('urls');
			$url = $urls->{$this->getEnv()} ?? '';

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
	 * Sets the base URL for the current environment.
	 */
	define('ENV_URL', envUrl());
}