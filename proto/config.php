<?php declare(strict_types=1);
namespace Proto
{
	use Proto\Patterns\Creational\Singleton;
	use Proto\Utils\Format\JsonFormat;
	use Proto\Utils\Files\File;
	use Proto\Database\ConnectionSettingsCache;

	/**
	 * Config Class
	 *
	 * Provides configuration settings management and retrieval
	 * using the Singleton design pattern.
	 *
	 * @package Proto
	 */
	final class Config extends Singleton
	{
		/**
		 * @var Config $instance The instance of the Config class
		 */
		protected static $instance = null;

		/**
		 * @var object $settings The configuration settings
		 */
		protected object $settings;

		/**
		 * @var string $envUrl The cached URL for the environment
		 */
		private static string $envUrl = '';

		/**
		 * Initializes settings and environment.
		 *
		 * @return void
		 */
		protected function __construct()
		{
			$this->loadSettings();
			$this->setEnv();
			$this->setErrorReporting();
		}

		/**
		 * Sets the environment based on the host.
		 *
		 * @return void
		 */
		private function setEnv(): void
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
		 * Sets error reporting based on the environment.
		 *
		 * @return void
		 */
		private function setErrorReporting(): void
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
		 * Loads settings from the settings file.
		 *
		 * @return void
		 */
		protected function loadSettings(): void
		{
			$contents = File::get(__DIR__ . '/../app/config/.env');
			if (!$contents)
			{
				throw new \Exception('Unable to locate settings file.');
			}

			$settings = JsonFormat::decode($contents);
			if (!$settings)
			{
				throw new \Exception('The file is invalid or empty.');
			}

			$this->settings = $settings;
		}

		/**
		 * This will get the settings.
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
		 * This will get a value by key.
		 *
		 * @param string $key
		 * @return mixed
		 */
		public function get(string $key): mixed
		{
			if (!isset($key))
			{
				return null;
			}

			return $this->settings->{$key} ?? null;
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
		 * This will get the env.
		 *
		 * @return string|null
		 */
		public function getEnv(): ?string
		{
			return $this->get('env');
		}

		/**
		 * This will get the url.
		 *
		 * @return string
		 */
		public function getUrl(): string
		{
			if (!empty(static::$envUrl))
			{
				return static::$envUrl;
			}

			$env = $this->getEnv();
			$urls = $this->get('urls');
			$url = $urls->{$env} ?? '';

			/**
			 * This will cache the url.
			 */
			static::$envUrl = $url;
			return $url;
		}

		/**
		 * This will get the url.
		 *
		 * @return string
		 */
		public static function url(): string
		{
			$config = static::getInstance();
			return $config->getUrl();
		}

		/**
		 * This wills get the db settings by connection name.
		 *
		 * @param string $connection
		 * @return object|null
		 */
		protected function getDbEnvSettings(string $connection): ?object
		{
			$connections = $this->settings->connections;
			return $connections->{$connection} ?? null;
		}

		/**
		 * This will get the db env settings.
		 *
		 * @param object $settings
		 * @return object|null
		 */
		protected function getEnvSettings(object $settings): ?object
		{
			$env = $this->getEnv();
			return $settings->{$env} ?? $settings->prod ?? $settings;
		}

		/**
		 * This will check if the connection more than one host.
		 *
		 * @param string $connection
		 * @param object $settings
		 * @return object
		 */
		private function checkMultiHost(string $connection, object $settings): object
		{
			$host = $settings->host ?? null;
			if (is_array($host) === false)
			{
				return $settings;
			}

			/**
			 * This will check to use chached settings if they have
			 * been set.
			 */
			$cacheSettings = ConnectionSettingsCache::get($connection);
			if (isset($cacheSettings))
			{
				return $cacheSettings;
			}

			/**
			 * This will randomly select a host.
			 */
			$host = $host[\array_rand($host)];
			$settings->host = $host;

			/**
			 * We need to cache the host setting to the connection
			 * to use the same host for each request to the
			 * connection.
			 */
			ConnectionSettingsCache::set($connection, $settings);

			return $settings;
		}

		/**
		 * This will check to get the db connection settings.
		 *
		 * @param string $connection
		 * @return object|null
		 */
		protected function getConnectionSettings(?string $connection = 'default'): ?object
		{
			$settings = $this->getDbEnvSettings($connection);
			if (empty($settings))
			{
				return null;
			}

			return $this->getEnvSettings($settings);
		}

		/**
		 * This will get the database connection settings.
		 *
		 * @param string|null $connection
		 * @return object
		 * @throws \Exception
		 */
		public function getDBSettings(?string $connection = 'default'): object
		{
			$settings = $this->getConnectionSettings($connection);
			if ($settings === null)
			{
				throw new \Exception('no connection settings are setup');
			}

			$connectionSettings = (object)$settings;

			/**
			 * This will get the connection host settings.
			 */
			return $this->checkMultiHost($connection, $connectionSettings);
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