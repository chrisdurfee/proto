<?php declare(strict_types=1);
namespace Proto\Patterns\Creational;

/**
 * Singleton
 *
 * A creational design pattern that ensures a class has only one
 * instance, while providing a global access point to this instance.
 *
 * PHP 8.5+: instances are stored in a private map keyed by class name
 * so subclasses need not redeclare incompatible typed $instance props.
 *
 * @package Proto\Patterns\Creational
 */
abstract class Singleton
{
	/**
	 * @var array<class-string, static>
	 */
	private static array $instances = [];

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
		$class = static::class;
		if (!isset(self::$instances[$class]))
		{
			self::$instances[$class] = new static();
		}

		return self::$instances[$class];
	}

	/**
	 * Resets the Singleton instance (primarily for testing).
	 *
	 * @return void
	 */
	public static function reset(): void
	{
		unset(self::$instances[static::class]);
	}

	/**
	 * Prevents cloning of the singleton instance.
	 */
	protected function __clone(): void
	{
	}

	/**
	 * Prevents unserialization of the singleton instance.
	 *
	 * @param array $data Serialized data
	 * @return void
	 */
	public function __unserialize(array $data): void
	{
		throw new \Exception("Cannot unserialize a singleton.");
	}
}