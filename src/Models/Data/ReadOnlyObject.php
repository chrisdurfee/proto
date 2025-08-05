<?php declare(strict_types=1);
namespace Proto\Models\Data;

/**
 * ReadOnlyObject
 *
 * Wraps a stdClass (or any object) to make it read-only, including nested objects/arrays.
 * Any attempt to set/unset a property will throw a RuntimeException.
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
	 */
	public function __construct(
		protected object $inner
	)
	{
		$this->inner = clone $inner;
	}

	/**
	 * Any get should delegate to the inner object, wrapping nested arrays/objects.
	 *
	 * @param string $name The name of the property to get.
	 * @return mixed The wrapped value, or null if it does not exist.
	 */
	public function __get(string $name): mixed
	{
		$value = $this->inner->{$name} ?? null;
		return $this->wrapValue($value);
	}

	/**
	 * Prevent writing to any property.
	 *
	 * @param string $name  The name of the property to set.
	 * @param mixed  $value The value to set the property to.
	 * @throws \RuntimeException If an attempt is made to set a property.
	 * @return void
	 */
	public function __set(string $name, mixed $value): void
	{
		throw new \RuntimeException("Cannot modify read-only data (tried to set '{$name}').");
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
		throw new \RuntimeException("Cannot unset properties on read-only data (tried to unset '{$name}').");
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
	 * Returns a clone of the inner stdClass (still immutable outside),
	 * but note that nested objects/arrays will not be wrapped here.
	 * Use getWrappedStdClass() if you need nested wrapping.
	 *
	 * @return object A clone of the inner object.
	 */
	public function toStdClass(): object
	{
		return clone $this->inner;
	}

	/**
	 * Returns the inner object as a fully wrapped stdClass, where all nested
	 * objects/arrays are converted to ReadOnlyObject/ReadOnlyArray.
	 *
	 * @return object The wrapped stdClass.
	 */
	public function getWrappedStdClass(): object
	{
		return $this->deepWrapObject(clone $this->inner);
	}

	/**
	 * Allow var_dump() or json_encode() to see properties.
	 *
	 * @return array<object|array> The array representation of this object.
	 */
	public function __debugInfo(): array
	{
		return (array)$this->getWrappedStdClass();
	}

	/**
	 * Recursively wraps an object’s properties so that nested objects/arrays
	 * become read-only as well.
	 *
	 * @param object $obj The object to wrap.
	 * @return object The wrapped object.
	 */
	private function deepWrapObject(object $obj): object
	{
		foreach ($obj as $key => $val)
		{
			if (is_object($val))
			{
				$obj->{$key} = $this->deepWrapObject(clone $val);
			}
			elseif (is_array($val))
			{
				$obj->{$key} = new ReadOnlyArray($val);
			}
		}

		return new ReadOnlyObject($obj);
	}

	/**
	 * Wraps a value:
	 *  - If it’s an object, return a ReadOnlyObject.
	 *  - If it’s an array, return a ReadOnlyArray.
	 *  - Otherwise, return as-is.
	 *
	 * @param mixed $value The value to wrap.
	 * @return mixed The wrapped value or the original scalar.
	 */
	private function wrapValue(mixed $value): mixed
	{
		if (is_object($value))
		{
			return new ReadOnlyObject($value);
		}

		if (is_array($value))
		{
			return new ReadOnlyArray($value);
		}

		return $value;
	}
}