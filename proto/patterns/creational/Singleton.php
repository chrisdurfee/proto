<?php declare(strict_types=1);
namespace Proto\Patterns\Creational;

/**
 * Singleton
 *
 * A creational design pattern that ensures a class has only one
 * instance, while providing a global access point to this instance.
 *
 * @package Proto\Patterns\Creational
 */
abstract class Singleton
{
	/**
	 * Holds the single instance of this class.
	 *
	 * @var static|null
	 */
	protected static ?self $instance = null;

	/**
	 * Prevents direct instantiation to enforce the singleton pattern.
	 */
	protected function __construct()
	{
	}

	/**
	 * Returns the Singleton instance of this class.
	 *
	 * @return static
	 */
	public static function getInstance(): static
	{
		if (static::$instance === null)
		{
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Prevents cloning of the singleton instance.
	 */
	protected function __clone(): void
	{
	}

	/**
	 * Prevents unserialization of the singleton instance.
	 */
	public function __wakeup(): void
	{
		throw new \Exception("Cannot unserialize a singleton.");
	}

	/**
	 * Prevents the unserialize method from being called.
	 *
	 * This method prevents the creation of another instance through
	 * deserialization, which would bypass the Singleton pattern.
	 *
	 * @param array $serializedData Serialized data
	 * @return void
	 */
	public function __unserialize(array $serializedData): void
	{
	}
}