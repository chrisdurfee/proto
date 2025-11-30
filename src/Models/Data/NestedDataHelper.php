<?php declare(strict_types=1);
namespace Proto\Models\Data;

use Proto\Utils\Strings;

/**
 * Class NestedDataHelper
 *
 * Provides methods to parse and group nested data. This version expects JSON
 * for nested structures. If the data is an array, it is returned as-is. If it
 * is a valid JSON string, it is decoded and keys are optionally converted to
 * camelCase. Otherwise, an empty array is returned.
 *
 * @package Proto\Models\Data
 */
class NestedDataHelper
{
	/** @var array Nested keys for data. */
	protected array $nestedKeys = [];

	/**
	 * This will add a key to the nested keys array.
	 *
	 * @param string $key
	 * @return void
	 */
	public function addKey(string $key): void
	{
		$this->nestedKeys[$key] = $key;
	}

	/**
	 * This will check if a key is in the nested keys array.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function isNestedKey(string $key): bool
	{
		return isset($this->nestedKeys[$key]);
	}

	/**
	 * Parses grouped data from a string or array. Expects JSON for nested data.
	 *
	 * @param mixed $group Group data (array or JSON string).
	 * @return array
	 */
	public function getGroupedData(mixed $group): array
	{
		if (is_array($group))
		{
			return $this->convertArrayItemsToObjects($group);
		}

		if (!$group)
		{
			return [];
		}

		$decoded = json_decode($group, true);
		if (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
		{
			return $this->convertKeysToCamelCase($decoded);
		}

		// Not valid JSON, return empty array
		return [];
	}

	/**
	 * Converts array items to objects if they are associative arrays.
	 *
	 * @param array $data Input array.
	 * @return array
	 */
	protected function convertArrayItemsToObjects(array $data): array
	{
		// Check if this looks like it should be a numeric array with object items
		// (e.g., from JSON like {"1":{...},"2":{...}} which should be [{...},{...}])
		if ($this->shouldBeNumericArray($data))
		{
			$data = array_values($data); // Re-index to 0, 1, 2...
		}

		$result = [];
		foreach ($data as $key => $item)
		{
			if (is_array($item) && $this->isAssociativeArray($item))
			{
				// Convert associative array to object and recursively process nested arrays
				$result[$key] = $this->arrayToObject($item);
			}
			elseif (is_array($item))
			{
				// Recursively process nested arrays
				$result[$key] = $this->convertArrayItemsToObjects($item);
			}
			else
			{
				$result[$key] = $item;
			}
		}
		return $result;
	}

	/**
	 * Determines if an array should be treated as a numeric array.
	 * This handles cases where JSON objects with numeric keys (e.g., {"1":{...},"2":{...}})
	 * should be converted to properly indexed numeric arrays ([{...},{...}]).
	 *
	 * @param array $array Input array.
	 * @return bool
	 */
	protected function shouldBeNumericArray(array $array): bool
	{
		if (empty($array))
		{
			return false;
		}

		// Check if all keys are numeric (int or numeric string) and all values are associative arrays (objects)
		$allKeysNumeric = true;
		$allValuesAreObjects = true;
		$keysNotSequential = false;

		$keys = array_keys($array);
		$expectedSequence = range(0, count($array) - 1);

		// Check if keys are not in sequential order starting from 0
		if ($keys !== $expectedSequence)
		{
			$keysNotSequential = true;
		}

		foreach ($array as $key => $value)
		{
			// Check if key is numeric (int or numeric string like "1", "2", "3")
			if (!is_int($key) && (!is_string($key) || !ctype_digit($key)))
			{
				$allKeysNumeric = false;
			}

			// Check if value is an associative array (representing an object)
			if (!is_array($value) || !$this->isAssociativeArray($value))
			{
				$allValuesAreObjects = false;
			}
		}

		// If all keys are numeric, keys are not sequential from 0, and all values are objects,
		// then re-index to create a proper numeric array
		return $allKeysNumeric && $keysNotSequential && $allValuesAreObjects;
	}

	/**
	 * Checks if an array is associative (has string keys).
	 *
	 * @param array $array Input array.
	 * @return bool
	 */
	protected function isAssociativeArray(array $array): bool
	{
		if (empty($array))
		{
			return false;
		}
		return array_keys($array) !== range(0, count($array) - 1);
	}

	/**
	 * Recursively converts an array to an object.
	 *
	 * @param array $array Input array.
	 * @return object
	 */
	protected function arrayToObject(array $array): object
	{
		$object = (object)[];
		foreach ($array as $key => $value)
		{
			if (is_array($value))
			{
				if ($this->isAssociativeArray($value))
				{
					$object->{$key} = $this->arrayToObject($value);
				}
				else
				{
					// Keep numeric arrays as arrays but convert nested items
					$object->{$key} = $this->convertArrayItemsToObjects($value);
				}
			}
			else
			{
				$object->{$key} = $value;
			}
		}
		return $object;
	}

	/**
	 * Recursively converts array keys to camelCase.
	 *
	 * @param array $data Input array.
	 * @return array
	 */
	protected function convertKeysToCamelCase(array $data): array
	{
		$result = [];
		foreach ($data as $key => $value)
		{
			if (is_array($value))
			{
				if ($this->isAssociativeArray($value))
				{
					// Convert to object and recursively process
					$newKey = is_string($key) ? $this->camelCase($key) : $key;
					$result[$newKey] = $this->arrayToObject($this->convertKeysToCamelCase($value));
				}
				else
				{
					// Numeric array - keep as array but process items
					$newKey = is_string($key) ? $this->camelCase($key) : $key;
					$result[$newKey] = $this->convertKeysToCamelCase($value);
				}
			}
			else
			{
				$newKey = is_string($key) ? $this->camelCase($key) : $key;
				$result[$newKey] = $value;

				if (is_string($key) && $this->isNestedKey($newKey))
				{
					$result[$newKey] = $this->getGroupedData($value);
				}
			}
		}
		return $result;
	}

	/**
	 * Converts a string to camelCase.
	 *
	 * @param string $string Input string.
	 * @return string
	 */
	protected function camelCase(string $string): string
	{
		return Strings::camelCase($string);
	}
}