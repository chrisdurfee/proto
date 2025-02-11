<?php declare(strict_types=1);
namespace Proto\Models\Data;

use Proto\Utils\Strings;

/**
 * Class NestedDataHelper
 *
 * Provides methods to parse and group nested data.
 *
 * @package Proto\Utils
 */
class NestedDataHelper
{
	/**
	 * Parses grouped data from a string or array.
	 *
	 * @param mixed $group Group data.
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

		$rows = explode('-:::-', (string)$group);
		if (count($rows) < 1)
        {
			return [];
		}

		$list = [];
		foreach ($rows as $row)
        {
			$cols = explode('-::-', $row);
			if (empty($cols[0]))
            {
				continue;
			}

			$item = $this->setRowItem($cols);
			if ($item !== null)
            {
				$list[] = $item;
			}
		}
		return $list;
	}

	/**
	 * Converts a row of column data into an associative object.
	 *
	 * @param array $cols Column data.
	 * @return array|object|null
	 */
	protected function setRowItem(array $cols): array|object|null
	{
		$item = [];
		foreach ($cols as $col)
        {
			$parts = explode('-:-', $col);
			if (count($parts) < 2)
            {
				$item[] = $parts[0];
				continue;
			}
			$key = $this->camelCase($parts[0]);
			$item[$key] = $parts[1];
		}
		return $this->isAssoc($item) ? (object)$item : (object)null;
	}

	/**
	 * Determines if an array is associative.
	 *
	 * @param array $array
	 * @return bool
	 */
	protected function isAssoc(array $array): bool
	{
		if ([] === $array)
        {
			return false;
		}
		return array_keys($array) !== range(0, count($array) - 1);
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