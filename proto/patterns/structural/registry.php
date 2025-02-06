<?php declare(strict_types=1);
namespace Proto\Patterns\Structural;

use Proto\Patterns\Creational\Singleton;

/**
 * Registry
 *
 * A structural design pattern that provides a global access point
 * for storing and retrieving values using keys. It extends the Singleton pattern
 * to ensure only one instance of the Registry exists.
 *
 * @package Proto\Patterns\Structural
 */
class Registry extends Singleton
{
	/**
	 * Holds registered items.
	 *
	 * @var array
	 */
	protected static array $items = [];

	/**
	 * Sets a value for the given key.
	 *
	 * @param string $key The key to store the value.
	 * @param mixed $value The value to be stored.
	 * @return void
	 */
	public static function set(string $key, mixed $value): void
	{
		self::$items[$key] = $value;
	}

	/**
	 * Retrieves the value for the given key.
	 *
	 * @param string $key The key to retrieve the value.
	 * @return mixed The value stored for the given key, or null if not found.
	 */
	public static function get(string $key): mixed
	{
		return self::$items[$key] ?? null;
	}

	/**
	 * Checks if a key exists in the registry.
	 *
	 * @param string $key The key to check.
	 * @return bool True if the key exists, false otherwise.
	 */
	public static function has(string $key): bool
	{
		return array_key_exists($key, self::$items);
	}

	/**
	 * Deletes an item by key.
	 *
	 * @param string $key The key of the item to be deleted.
	 * @return void
	 */
	public static function delete(string $key): void
	{
		unset(self::$items[$key]);
	}

	/**
	 * Clears all items in the registry.
	 *
	 * @return void
	 */
	public static function clear(): void
	{
		self::$items = [];
	}

	/**
	 * Magic method to set a value for the given key.
	 *
	 * @param string $key The key to store the value.
	 * @param mixed $value The value to be stored.
	 * @return void
	 */
	public function __set(string $key, mixed $value): void
	{
		self::set($key, $value);
	}

	/**
	 * Magic method to retrieve the value for the given key.
	 *
	 * @param string $key The key to retrieve the value.
	 * @return mixed The value stored for the given key, or null if not found.
	 */
	public function __get(string $key): mixed
	{
		return self::get($key);
	}

	/**
	 * Magic method to check if a key exists.
	 *
	 * @param string $key The key to check.
	 * @return bool True if the key exists, false otherwise.
	 */
	public function __isset(string $key): bool
	{
		return self::has($key);
	}

	/**
	 * Magic method to delete an item by key.
	 *
	 * @param string $key The key of the item to be deleted.
	 * @return void
	 */
	public function __unset(string $key): void
	{
		self::delete($key);
	}
}