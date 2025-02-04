<?php declare(strict_types=1);
namespace Proto\Patterns\Creational;

/**
 * Class Singleton
 *
 * Singleton is a creational design pattern that ensures a class has only one
 * instance, while providing a global access point to this instance.
 *
 * @package Proto\Patterns\Creational
 */
class Singleton
{
	/**
	 * @var static $instance Holds the single instance of this class
	 */
	protected static $instance = null;

	/**
	 * Singleton constructor.
	 *
	 * Declared as protected to prevent creating a new instance externally
	 * and to make the class extendable.
	 *
	 * @return void
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
		return static::$instance ?? (static::$instance = new static());
	}

	/**
	 * Prevents the clone method from being called.
	 *
	 * Declared as protected to make the class extendable.
	 *
	 * @return void
	 */
	protected function __clone(): void
	{
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