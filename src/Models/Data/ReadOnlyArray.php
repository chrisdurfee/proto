<?php declare(strict_types=1);
namespace Proto\Models\Data;

/**
 * ReadOnlyArray
 *
 * Wraps a PHP array to make it read-only, including nested arrays/objects.
 * Any attempt to modify offsets will throw a RuntimeException.
 *
 * @package Proto\Models\Data
 */
final class ReadOnlyArray implements \ArrayAccess, \IteratorAggregate, \Countable
{
	/**
	 * Constructor.
	 *
	 * @param array<mixed> $inner The inner array to wrap.
	 */
	public function __construct(
		protected array $inner
	)
	{
		$this->inner = $this->deepCloneArray($inner);
	}

	/**
	 * Returns the value at the given offset, wrapped if needed.
	 *
	 * @param mixed $offset The array key to retrieve.
	 * @return mixed The wrapped value, or null if undefined.
	 */
	public function offsetGet(mixed $offset): mixed
	{
		if (! isset($this->inner[$offset]))
		{
			return null;
		}

		return $this->wrapValue($this->inner[$offset]);
	}

	/**
	 * Checks if the given offset exists and is not null.
	 *
	 * @param mixed $offset The array key to check.
	 * @return bool True if the key exists and is not null.
	 */
	public function offsetExists(mixed $offset): bool
	{
		return isset($this->inner[$offset]);
	}

	/**
	 * Prevent setting any array element.
	 *
	 * @param mixed $offset The array key to set.
	 * @param mixed $value  The value to assign.
	 * @throws \RuntimeException If an attempt is made to set an element.
	 */
	public function offsetSet(mixed $offset, mixed $value): void
	{
		throw new \RuntimeException("Cannot modify read-only array (tried to set offset '{$offset}').");
	}

	/**
	 * Prevent unsetting any array element.
	 *
	 * @param mixed $offset The array key to unset.
	 * @throws \RuntimeException If an attempt is made to unset an element.
	 */
	public function offsetUnset(mixed $offset): void
	{
		throw new \RuntimeException("Cannot unset read-only array (tried to unset offset '{$offset}').");
	}

	/**
	 * Returns an iterator over the inner array, with values wrapped as needed.
	 *
	 * @return \ArrayIterator<ReadOnlyObject|ReadOnlyArray|mixed>
	 */
	public function getIterator(): \ArrayIterator
	{
		$wrapped = [];
		foreach ($this->inner as $key => $val)
		{
			$wrapped[$key] = $this->wrapValue($val);
		}

		return new \ArrayIterator($wrapped);
	}

	/**
	 * Returns the number of elements in the array.
	 *
	 * @return int The element count.
	 */
	public function count(): int
	{
		return count($this->inner);
	}

	/**
	 * Recursively wraps array elements:
	 *  - If element is an object, wrap in ReadOnlyObject.
	 *  - If element is an array, wrap in ReadOnlyArray.
	 *  - Otherwise, leave as-is.
	 *
	 * @param mixed $value The value to wrap.
	 * @return mixed The wrapped value or original scalar.
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

	/**
	 * Deep-clones a nested array so that you cannot modify the original references.
	 *
	 * @param array<mixed> $arr The array to clone.
	 * @return array<mixed> A deep-cloned copy.
	 */
	private function deepCloneArray(array $arr): array
	{
		$copy = [];
		foreach ($arr as $key => $val)
		{
			if (is_object($val))
			{
				$copy[$key] = clone $val;
			}
			elseif (is_array($val))
			{
				$copy[$key] = $this->deepCloneArray($val);
			}
			else
			{
				$copy[$key] = $val;
			}
		}
		return $copy;
	}
}