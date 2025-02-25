<?php declare(strict_types=1);
namespace Proto\Module
{
	use Exception;

	/**
	 * Modules
	 *
	 * This class is responsible for managing and registering modules.
	 *
	 * @package Proto\Module
	 */
	class Modules
	{
		/**
		 * Optional API key or other shared dependencies.
		 *
		 * @var string
		 */
		protected string $apiKey;

		/**
		 * Array to hold registered modules.
		 *
		 * @var array
		 */
		protected array $registered = [];

		/**
		 * Register a module with a given key and a factory callable.
		 *
		 * @param string $key
		 * @param callable $factory A callable that returns a module instance.
		 * @return void
		 */
		public function registerModule(string $key, callable $factory): void
		{
			$this->registered[$key] = $factory;
		}

		/**
		 * Magic method to access a registered module dynamically.
		 *
		 * For example, calling modules()->billing() will invoke the registered factory for "billing".
		 *
		 * @param string $name The module key.
		 * @param array $arguments
		 * @return mixed
		 * @throws Exception
		 */
		public function __call(string $name, array $arguments)
		{
			if (isset($this->registered[$name]))
			{
				return call_user_func($this->registered[$name], ...$arguments);
			}

			throw new Exception("Module '{$name}' is not registered.");
		}
	}
}

namespace
{
	use Proto\Module\Modules;

	/**
	 * @var Modules $modules
	 */
	$modules = new Modules();

	/**
	 * This will return the modules instance.
	 *
	 * @return Modules
	 */
	function modules(): Modules
	{
		global $modules;
		return $modules;
	}

	/**
	 * This will register a module with a given key and a factory callable.
	 *
	 * @param string $key
	 * @param callable $factory A callable that returns a module instance.
	 * @return void
	 */
	function registerModule(string $key, callable $factory): void
	{
		global $modules;
		$modules->registerModule($key, $factory);
	}
}