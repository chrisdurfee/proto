<?php declare(strict_types=1);
namespace Proto\Models;

use Proto\Utils\Strings;
use Proto\Utils\Arrays;

/**
 * Class DataMapper
 *
 * Maps the model data and provides methods to manipulate and access the data.
 *
 * @package Proto\Models
 */
class DataMapper
{
	/**
	 * @var array $joinFields Join fields.
	 */
	protected $joinFields = [];

	/**
	 * @var array $alias Field aliases.
	 */
	protected $alias = [];

	/**
	 * @var array $fieldBlacklist Field blacklist.
	 */
	protected $fieldBlacklist = [];

	/**
	 * @var object|null $data Data storage.
	 */
	protected ?object $data = null;

	/**
	 * @var bool $isSnakeCase Whether or not the data is snake_case.
	 */
	protected bool $isSnakeCase = true;

	/**
	 * DataMapper constructor.
	 *
	 * @param array $fields Fields to be mapped.
	 * @param array $joins Join fields.
	 * @param array $fieldBlacklist Fields to be blacklisted.
	 * @return void
	 */
	public function __construct(array &$fields, array &$joins = [], array &$fieldBlacklist = [])
	{
		$this->fieldBlacklist = $fieldBlacklist;
		$this->setup($fields, $joins);
	}

	/**
	 * Sets up the data and adds fields and join fields.
	 *
	 * @param array $fields Fields to be mapped.
	 * @param array $joins Join fields.
	 * @return void
	 */
	protected function setup(array &$fields = [], array &$joins = []): void
	{
		$this->data = (object)[];
		$this->setupFieldsToData($fields);
		$this->setJoinsToData($joins);
	}

	/**
	 * This will get if the data is snake_case.
	 *
	 * @return bool
	 */
	public function isSnakeCase(): bool
	{
		return $this->isSnakeCase;
	}

	/**
	 * This will set if the data is snake_case.
	 *
	 * @param bool $isSnakeCase Whether or not the data is snake_case.
	 * @return void
	 */
	public function setSnakeCase(bool $isSnakeCase): void
	{
		$this->isSnakeCase = $isSnakeCase;
	}

	/**
	 * Adds fields to the data object.
	 *
	 * @param array $fields Fields to be mapped.
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
	 * Adds join fields to the data object.
	 *
	 * @param array $joins Join fields.
	 * @return void
	 */
	protected function setJoinsToData(array &$joins): void
	{
		if (count($joins) < 1)
		{
			return;
		}

		$joinFields = &$this->joinFields;
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

			// Adds the fields to the data object
			foreach ($joiningFields as $field)
			{
				$field = $this->checkAliasField($field);
				array_push($joinFields, $field);
				$this->setDataField($field, null);
			}
		}
	}

	/**
	 * Checks if a field has an alias and returns the proper field name.
	 *
	 * @param mixed $field Field name or field alias.
	 * @return mixed
	 */
	protected function checkAliasField(mixed $field): mixed
	{
		if (is_array($field) === false)
		{
			return Strings::camelCase($field);
		}

		// Tracks the alias
		$this->alias[$field[1]] = (is_array($field[0]) === false) ? Strings::camelCase($field[0]) : $field[0];
		return $field[1];
	}

	/**
	 * Retrieves the mapped field name.
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
	 * Sets a field and its value in the data object.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Field value.
	 * @return void
	 */
	protected function setDataField(string $key, mixed $value): void
	{
		$this->data->{$key} = $value;
	}

	/**
	 * Returns the data formatted into an object with snake_case keys for use in database queries.
	 *
	 * @return object
	 */
	public function map(): object
	{
		$obj = [];

		$data = $this->data;
		$joinFields = $this->joinFields;
		foreach ($data as $key => $val)
		{
			if (is_null($val) || in_array($key, $joinFields) || is_array($val))
			{
				continue;
			}

			// Converts the alias to the normal column name if found
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
	 * Prepares the key name.
	 *
	 * @param string $key Key name.
	 * @return string
	 */
	protected function prepareKeyName(string $key): string
	{
		return $this->isSnakeCase ? $this->snakeCase($key) : $key;
	}

	/**
	 * Retrieves grouped data.
	 *
	 * @param mixed $group Group data.
	 * @return array
	 */
	protected function getGroupedData(mixed $group): array
	{
		if (gettype($group) === 'array')
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
				array_push($list, $item);
			}
		}

		return $list;
	}

	/**
	 * Sets a row item.
	 *
	 * @param array $list List of row items.
	 * @param array $cols Column data.
	 *
	 * @return array|object
	 */
	protected function setRowItem(array &$list, array $cols)
	{
		$item = [];

		foreach ($cols as $col)
		{
			$parts = explode('-:-', $col);
			if (count($parts) < 2)
			{
				array_push($list, $parts[0]);
				continue;
			}

			$key = Strings::camelCase($parts[0]);
			$item[$key] = $parts[1];
		}
		return (Arrays::isAssoc($item)) ? (object)$item : null;
	}

	/**
	 * Sets field data.
	 *
	 * @param object $newData New data object.
	 * @return void
	 */
	protected function setFields($newData): void
	{
		if (!$newData)
		{
			return;
		}

		$data = $this->data;
		foreach ($newData as $key => $val)
		{
			$keyCamelCase = Strings::camelCase($key);
			if (property_exists($data, $keyCamelCase) === false)
			{
				continue;
			}

			// Gets grouped rows
			if (gettype($data->{$keyCamelCase}) === 'array')
			{
				$val = $this->getGroupedData($val);
			}

			$this->setDataField($keyCamelCase, $val);
		}
	}

	/**
	 * Sets data. The parameter can be an object or key-value pair.
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
			$firstArg = (object)[
				$firstArg => $value
			];
		}

		$this->setFields($firstArg);
	}

	/**
	 * Retrieves the value of a data field.
	 *
	 * @param string $key Field name.
	 * @return mixed
	 */
	public function get(string $key): mixed
	{
		return $this->data->{$key} ?? null;
	}

	/**
	 * Converts a string to snake_case.
	 *
	 * @param string $str String to be converted.
	 * @return string
	 */
	protected function snakeCase(string $str): string
	{
		return Strings::snakeCase($str);
	}

	/**
	 * Converts batch rows format data to mapped data.
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
		$fieldsBlacklist = $this->fieldBlacklist;
		$data = $this->data;

		foreach ($rows as $row)
		{
			$obj = new \stdClass;
			foreach ($data as $key => $val)
			{
				if (array_search($key, $fieldsBlacklist) !== false)
				{
					continue;
				}

				$keyName = $this->prepareKeyName($key);
				$value = $row->{$keyName} ?? null;

				if (gettype($val) === 'array')
				{
					$value = $this->getGroupedData($value);
				}

				$obj->{$key} = $value;
			}

			array_push($formatted, $obj);
		}
		return $formatted;
	}

	/**
	 * Retrieves the mapped data.
	 *
	 * @return object
	 */
	public function getData(): object
	{
		$obj = [];

		$fieldsBlacklist = $this->fieldBlacklist;
		$data = $this->data;
		foreach ($data as $key => $value)
		{
			if (array_search($key, $fieldsBlacklist) !== false)
			{
				continue;
			}
			$obj[$key] = $value;
		}
		return (object)$obj;
	}
}