<?php declare(strict_types=1);
namespace Proto\Models;

use Proto\Utils\Strings;
use Proto\Utils\Arrays;

/**
 * Class DataMapper
 *
 * Maps model data and provides methods to manipulate and access the data.
 *
 * @package Proto\Models
 */
class DataMapper
{
	/**
	 * Join fields.
	 *
	 * @var array
	 */
	protected array $joinFields = [];

	/**
	 * Field aliases.
	 *
	 * @var array
	 */
	protected array $alias = [];

	/**
	 * Field blacklist.
	 *
	 * @var array
	 */
	protected array $fieldBlacklist = [];

	/**
	 * Data storage.
	 *
	 * @var object|null
	 */
	protected ?object $data = null;

	/**
	 * Indicates if data is snake_case.
	 *
	 * @var bool
	 */
	protected bool $isSnakeCase = true;

	/**
	 * DataMapper constructor.
	 *
	 * @param array $fields Fields to map.
	 * @param array $joins Join definitions.
	 * @param array $fieldBlacklist Fields to blacklist.
	 */
	public function __construct(array &$fields, array &$joins = [], array &$fieldBlacklist = [])
	{
		$this->fieldBlacklist = $fieldBlacklist;
		$this->setup($fields, $joins);
	}

	/**
	 * Set up data mapper.
	 *
	 * @param array $fields Fields to map.
	 * @param array $joins Join definitions.
	 * @return void
	 */
	protected function setup(array &$fields = [], array &$joins = []): void
	{
		$this->data = (object)[];
		$this->setupFieldsToData($fields);
		$this->setJoinsToData($joins);
	}

	/**
	 * Check if data is snake_case.
	 *
	 * @return bool
	 */
	public function isSnakeCase(): bool
	{
		return $this->isSnakeCase;
	}

	/**
	 * Set snake_case flag.
	 *
	 * @param bool $isSnakeCase
	 * @return void
	 */
	public function setSnakeCase(bool $isSnakeCase): void
	{
		$this->isSnakeCase = $isSnakeCase;
	}

	/**
	 * Add fields to the data object.
	 *
	 * @param array $fields Fields to add.
	 * @return void
	 */
	protected function setupFieldsToData(array &$fields): void
	{
		if (count($fields) < 1)
		{
			return;
		}

		foreach ($fields as $key)
		{
			$key = $this->checkAliasField($key);
			$this->setDataField($key, null);
		}
	}

	/**
	 * Add join fields to the data object.
	 *
	 * @param array $joins Join definitions.
	 * @return void
	 */
	protected function setJoinsToData(array &$joins): void
	{
		if (count($joins) < 1)
		{
			return;
		}

		foreach ($joins as $join)
		{
			if ($join->isMultiple())
			{
				$as = Strings::camelCase($join->getAs());
				$this->setDataField($as, []);
				continue;
			}

			$joiningFields = $join->getFields() ?? false;
			if (!$joiningFields)
			{
				continue;
			}

			foreach ($joiningFields as $field)
			{
				$field = $this->checkAliasField($field);
				$this->joinFields[] = $field;
				$this->setDataField($field, null);
			}
		}
	}

	/**
	 * Check if a field has an alias and return proper field name.
	 *
	 * @param mixed $field Field name or alias.
	 * @return mixed
	 */
	protected function checkAliasField(mixed $field): mixed
	{
		if (!is_array($field))
		{
			return Strings::camelCase($field);
		}

		$this->alias[$field[1]] = !is_array($field[0]) ? Strings::camelCase($field[0]) : $field[0];
		return $field[1];
	}

	/**
	 * Get the mapped field name.
	 *
	 * @param string $field Field name.
	 * @return string|null
	 */
	protected function getMappedField(string $field): ?string
	{
		$alias = $this->alias[$field] ?? null;
		if (isset($alias) && is_array($alias))
		{
			return null;
		}

		return $alias ?? $field;
	}

	/**
	 * Set a field in the data object.
	 *
	 * @param string $key Field name.
	 * @param mixed $value Field value.
	 * @return void
	 */
	protected function setDataField(string $key, mixed $value): void
	{
		$this->data->{$key} = $value;
	}

	/**
	 * Map data to snake_case keys.
	 *
	 * @return object
	 */
	public function map(): object
	{
		$obj = [];
		foreach ($this->data as $key => $val)
		{
			if (is_null($val) || in_array($key, $this->joinFields) || is_array($val))
			{
				continue;
			}

			$alias = $this->alias[$key] ?? null;
			if ($alias && is_array($alias))
			{
				continue;
			}

			$key = $alias ?? $key;
			$keyName = $this->prepareKeyName($key);
			$obj[$keyName] = $val;
		}
		return (object)$obj;
	}

	/**
	 * Prepare a key name.
	 *
	 * @param string $key Key name.
	 * @return string
	 */
	protected function prepareKeyName(string $key): string
	{
		return $this->isSnakeCase ? $this->snakeCase($key) : $key;
	}

	/**
	 * Get grouped data from a string.
	 *
	 * @param mixed $group Group data.
	 * @return array
	 */
	protected function getGroupedData(mixed $group): array
	{
		if (is_array($group))
		{
			return $group;
		}

		if (!$group)
		{
			return [];
		}

		$rows = explode('-:::-', $group);
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

			$item = $this->setRowItem($list, $cols);
			if ($item)
			{
				$list[] = $item;
			}
		}
		return $list;
	}

	/**
	 * Set a row item.
	 *
	 * @param array $list List of rows.
	 * @param array $cols Column data.
	 * @return array|object|null
	 */
	protected function setRowItem(array &$list, array $cols): array|object|null
	{
		$item = [];
		foreach ($cols as $col)
		{
			$parts = explode('-:-', $col);
			if (count($parts) < 2)
			{
				$list[] = $parts[0];
				continue;
			}

			$key = Strings::camelCase($parts[0]);
			$item[$key] = $parts[1];
		}
		return Arrays::isAssoc($item) ? (object)$item : null;
	}

	/**
	 * Set data fields.
	 *
	 * @param object $newData New data object.
	 * @return void
	 */
	protected function setFields(object $newData): void
	{
		if (!$newData)
		{
			return;
		}

		foreach ($newData as $key => $val)
		{
			$keyCamelCase = Strings::camelCase($key);
			if (!property_exists($this->data, $keyCamelCase))
			{
				continue;
			}

			if (is_array($this->data->{$keyCamelCase}))
			{
				$val = $this->getGroupedData($val);
			}
			$this->setDataField($keyCamelCase, $val);
		}
	}

	/**
	 * Set data values.
	 *
	 * @return void
	 */
	public function set(): void
	{
		$args = func_get_args();
		if (count($args) < 1)
		{
			return;
		}

		$firstArg = $args[0];
		if (!is_object($firstArg))
		{
			$value = $args[1] ?? null;
			$firstArg = (object)[$args[0] => $value];
		}
		$this->setFields($firstArg);
	}

	/**
	 * Get a data field value.
	 *
	 * @param string $key Field name.
	 * @return mixed
	 */
	public function get(string $key): mixed
	{
		return $this->data->{$key} ?? null;
	}

	/**
	 * Convert a string to snake_case.
	 *
	 * @param string $str Input string.
	 * @return string
	 */
	protected function snakeCase(string $str): string
	{
		return Strings::snakeCase($str);
	}

	/**
	 * Convert rows to mapped data.
	 *
	 * @param array $rows Array of rows.
	 * @return array
	 */
	public function convertRows(array $rows = []): array
	{
		if (count($rows) < 1)
		{
			return [];
		}

		$formatted = [];
		foreach ($rows as $row)
		{
			$obj = new \stdClass;
			foreach ($this->data as $key => $val)
			{
				if (in_array($key, $this->fieldBlacklist))
				{
					continue;
				}

				$keyName = $this->prepareKeyName($key);
				$value = $row->{$keyName} ?? null;
				if (is_array($val))
				{
					$value = $this->getGroupedData($value);
				}

				$obj->{$key} = $value;
			}
			$formatted[] = $obj;
		}
		return $formatted;
	}

	/**
	 * Get the mapped data.
	 *
	 * @return object
	 */
	public function getData(): object
	{
		$obj = [];
		foreach ($this->data as $key => $value)
		{
			if (in_array($key, $this->fieldBlacklist))
			{
				continue;
			}
			$obj[$key] = $value;
		}
		return (object)$obj;
	}
}