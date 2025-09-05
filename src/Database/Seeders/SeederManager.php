<?php declare(strict_types=1);
namespace Proto\Database\Seeders;

/**
 * SeederManager
 *
 * Manages the execution of database seeders.
 *
 * @package Proto\Database\Seeders
 */
class SeederManager
{
	/**
	 * @var array $seeders Registered seeders
	 */
	protected array $seeders = [];

	/**
	 * Registers a seeder.
	 *
	 * @param string $seederClass
	 * @return self
	 */
	public function register(string $seederClass): self
	{
		if (!in_array($seederClass, $this->seeders))
		{
			$this->seeders[] = $seederClass;
		}
		return $this;
	}

	/**
	 * Registers multiple seeders.
	 *
	 * @param array $seederClasses
	 * @return self
	 */
	public function registerMany(array $seederClasses): self
	{
		foreach ($seederClasses as $seederClass)
		{
			$this->register($seederClass);
		}
		return $this;
	}

	/**
	 * Runs a specific seeder.
	 *
	 * @param string $seederClass
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function run(string $seederClass): void
	{
		if (!class_exists($seederClass))
		{
			throw new \InvalidArgumentException("Seeder class [{$seederClass}] does not exist");
		}

		if (!is_subclass_of($seederClass, Seeder::class))
		{
			throw new \InvalidArgumentException("Class [{$seederClass}] must extend " . Seeder::class);
		}

		$seeder = new $seederClass();
		$seeder->run();
	}

	/**
	 * Runs all registered seeders.
	 *
	 * @return void
	 */
	public function runAll(): void
	{
		foreach ($this->seeders as $seederClass)
		{
			$this->run($seederClass);
		}
	}

	/**
	 * Runs multiple seeders.
	 *
	 * @param array $seederClasses
	 * @return void
	 */
	public function runMany(array $seederClasses): void
	{
		foreach ($seederClasses as $seederClass)
		{
			$this->run($seederClass);
		}
	}

	/**
	 * Gets all registered seeders.
	 *
	 * @return array
	 */
	public function getSeeders(): array
	{
		return $this->seeders;
	}

	/**
	 * Clears all registered seeders.
	 *
	 * @return void
	 */
	public function clear(): void
	{
		$this->seeders = [];
	}
}