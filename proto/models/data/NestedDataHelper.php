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
			return $group;
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
	 * Recursively converts array keys to camelCase.
	 *
	 * @param array $data Input array.
	 * @return array
	 */
	protected function convertKeysToCamelCase(array $data): array
	{
		foreach ($data as $key => $value)
		{
			if (is_array($value))
			{
				$data[$key] = $this->convertKeysToCamelCase($value);
			}
			elseif (is_string($key))
			{
				$newKey = $this->camelCase($key);
				if ($newKey !== $key)
				{
					$data[$newKey] = $value;
					unset($data[$key]);
				}
			}
		}
		return $data;
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