<?php declare(strict_types=1);
namespace Proto\Models\Data;

/**
 * ReadOnlyObject
 *
 * Wraps a stdClass (or any object) to make it read-only. Any attempt
 * to set/unset a property will throw a RuntimeException.
 *
 * @package Proto\Models\Data
 */
final class ReadOnlyObject
{
	/**
	 * Constructor.
	 *
	 * @param object $inner The inner object to wrap. It will be cloned to ensure
	 * that the original object cannot be modified.
	 * @return void
	 */
	public function __construct(
		protected object $inner
	)
	{
	}

	/**
	 * Any get should just delegate to the inner object.
	 *
	 * @param string $name The name of the property to get.
	 * @return mixed The value of the property, or null if it does not exist.
	 */
	public function __get(string $name): mixed
	{
		return $this->inner->{$name} ?? null;
	}

	/**
	 * Prevent writing to any property.
	 *
	 * @param string $name The name of the property to set.
	 * @param mixed $value The value to set the property to.
	 * @throws \RuntimeException If an attempt is made to set a property.
	 * @return void
	 */
	public function __set(string $name, mixed $value): void
	{
		throw new \RuntimeException("Cannot modify read-only data (tried to set '\$name').");
	}

	/**
	 * Prevent unsetting any property.
	 *
	 * @param string $name The name of the property to unset.
	 * @throws \RuntimeException If an attempt is made to unset a property.
	 * @return void
	 */
	public function __unset(string $name): void
	{
		throw new \RuntimeException("Cannot unset properties on read-only data (tried to unset '\$name').");
	}

	/**
	 * If someone calls isset($ro->foo) or empty($ro->foo), let it delegate
	 * to the inner object.
	 *
	 * @param string $name The name of the property to check.
	 * @return bool True if the property exists and is not null, false otherwise.
	 */
	public function __isset(string $name): bool
	{
		return isset($this->inner->{$name});
	}

	/**
	 * In case you need the raw stdClass again (but note: it will be
	 * a clone, not the live Data::$data itself).
	 *
	 * @return object A clone of the inner object.
	 */
	public function toStdClass(): object
	{
		return clone $this->inner;
	}

	/**
	 * If you want to allow iteration or json_encode(...) on this wrapper,
	 * add __debugInfo() or implement IteratorAggregate. For now, the simplest:
	 *
	 * @return array
	 */
	public function __debugInfo(): array
	{
		// This will let var_dump() show the properties, for example.
		return (array)$this->inner;
	}
}
