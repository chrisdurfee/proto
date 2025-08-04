<?php declare(strict_types=1);
namespace Proto\Models\Data;

use Proto\Models\Joins\ModelJoin;
use Proto\Utils\Strings;

/**
 * Class Data
 *
 * Manages model data using a property mapper strategy and nested data helper.
 * Data is stored internally in camelCase. When mapping data for storage,
 * keys are converted using the selected mapper strategy.
 *
 * @package Proto\Models\Data
 */
class Data
{
	/** @var object Internal data storage. */
	protected object $data;

	/** @var array Field alias mappings. */
	protected array $alias = [];

	/** @var array List of join field keys. */
	protected array $joinFields = [];

	/** @var array Field blacklist. */
	protected array $fieldBlacklist = [];

	/** @var AbstractMapper Mapper instance. */
	protected AbstractMapper $mapper;

	/** @var NestedDataHelper Helper for nested data grouping. */
	protected NestedDataHelper $nestedDataHelper;

	/**
	 * Data constructor.
	 *
	 * @param array $fields Fields to initialize.
	 * @param array $joins Join definitions.
	 * @param array $fieldBlacklist Fields to exclude.
	 * @param bool $snakeCase If true, mapping will convert keys to snake_case.
	 */
	public function __construct(
		array $fields,
		array $joins = [],
		array $fieldBlacklist = [],
		bool $snakeCase = false
	)
	{
		$this->fieldBlacklist = $fieldBlacklist;

		$mapperType = ($snakeCase) ? 'snake' : 'default';
		$this->mapper = Mapper::factory($mapperType);

		$this->nestedDataHelper = new NestedDataHelper();
		$this->setup($fields, $joins);
	}

	/**
	 * Initializes the data object with fields and joins.
	 *
	 * @param array $fields Fields to map.
	 * @param array $joins Join definitions.
	 * @return void
	 */
	protected function setup(array $fields, array $joins): void
	{
		$this->data = (object)[];
		$this->setupFieldsToData($fields);
		$this->setupJoinsToData($joins);
	}

	/**
	 * Initializes fields into the internal data object.
	 *
	 * @param array $fields Fields to add.
	 * @return void
	 */
	protected function setupFieldsToData(array $fields): void
	{
		if (empty($fields))
		{
			return;
		}

		foreach ($fields as $field)
		{
			$key = $this->checkAliasField($field);
			$this->setDataField($key, null);
		}
	}

	/**
	 * Checks for an alias; if none, returns the camelCase version of the field.
	 *
	 * @param mixed $field Field name or [original, alias] pair.
	 * @return mixed
	 */
	protected function checkAliasField(mixed $field): mixed
	{
		if (!is_array($field))
		{
			return Strings::camelCase($field);
		}

		$this->alias[$field[1]] = (is_array($field[0]) === false)
			? Strings::camelCase($field[0])
			: $field[0];

		return $field[1];
	}

	/**
	 * Initializes join fields into the internal data object.
	 *
	 * @param array<ModelJoin> $joins Join definitions.
	 * @return void
	 */
	protected function setupJoinsToData(array $joins): void
	{
		if (empty($joins))
		{
			return;
		}

		foreach ($joins as $join)
		{
			$this->checkJoinFields($join);
		}
	}

	/**
	 * This will check the join fields.
	 *
	 * @param ModelJoin $join
	 * @param int $depth
	 * @return void
	 */
	private function checkJoinFields(ModelJoin $join, int $depth = 0): void
	{
		$this->setJoinField($join, $depth);

		$childJoin = $join->getMultipleJoin();
		if ($childJoin)
		{
			$this->checkJoinFields($childJoin, ++$depth);
		}
	}

	/**
	 * This will set the join fields into the data object.
	 *
	 * @param ModelJoin $join
	 * @param int $depth
	 * @return void
	 */
	private function setJoinField(ModelJoin $join, int $depth = 0): void
	{
		/**
		 * This will exclude bridge joins.
		 */
		if ($join->isMultiple() && $join->getFields())
		{
			$name = $join->getAs() ?? $join->getTableName();
			$key = Strings::camelCase($name);
			$this->nestedDataHelper->addKey($key);

			/**
			 * We only root joins to the root level.
			 */
			if ($depth < 2)
			{
				$this->setDataField($key, []);
				$this->joinFields[] = $key;
			}
			return;
		}

		$joiningFields = $join->getFields() ?? false;
		if (!$joiningFields)
		{
			return;
		}

		foreach ($joiningFields as $field)
		{
			$key = $this->checkAliasField($field);
			$this->joinFields[] = $key;
			$this->setDataField($key, null);
		}
	}

	/**
	 * Adds a join field to the data object.
	 *
	 * @param string $key Field name.
	 * @param mixed $value Field value (optional).
	 * @return void
	 */
	public function addJoinField(string $key, mixed $value = null): void
	{
		$key = Strings::camelCase($key);
		$this->nestedDataHelper->addKey($key);

		// Ensure this key is treated as a joinField (so map() will skip it).
		if (!in_array($key, $this->joinFields, true))
		{
			$this->joinFields[] = $key;
		}

		if ($value !== null)
		{
			$this->setDataField($key, $value);
		}
	}

	/**
	 * Checks if a field exists in the data object.
	 *
	 * @param string $key Field name.
	 * @return bool
	 */
	public function has(string $key): bool
	{
		$key = Strings::camelCase($key);
		return property_exists($this->data, $key)
			&& !in_array($key, $this->fieldBlacklist, true);
	}

	/**
	 * Unsets a field in the data object.
	 *
	 * @param string $key Field name.
	 * @return void
	 */
	public function unset(string $key): void
	{
		$key = Strings::camelCase($key);
		if (property_exists($this->data, $key))
		{
			unset($this->data->{$key});
		}
	}

	/**
	 * Sets a field in the data object.
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
	 * Sets multiple data fields from an object.
	 *
	 * @param object $newData New data.
	 * @return void
	 */
	protected function setFields(object $newData): void
	{
		foreach ($newData as $key => $val)
		{
			$keyMapped = Strings::camelCase($key);
			if (!property_exists($this->data, $keyMapped))
			{
				continue;
			}

			if ($this->nestedDataHelper->isNestedKey(($keyMapped)))
			{
				$val = $this->nestedDataHelper->getGroupedData($val);
			}

			$this->setDataField($keyMapped, $val);
		}
	}

	/**
	 * Sets data values. Accepts either a key/value pair or an object.
	 *
	 * @return void
	 */
	public function set(): void
	{
		$args = func_get_args();
		if (empty($args))
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
	 * Retrieves a data field value.
	 *
	 * @param string $key Field name.
	 * @return mixed
	 */
	public function get(string $key): mixed
	{
		return $this->data->{$key} ?? null;
	}

	/**
	 * Returns the internal data as an object with camelCase keys.
	 *
	 * @return object
	 */
	public function getData(): object
	{
		$out = [];
		foreach ($this->data as $key => $value)
		{
			if (in_array($key, $this->fieldBlacklist, true))
			{
				continue;
			}

			$out[$key] = $value;
		}

		return (object)$out;
	}

	/**
	 * Returns a **read-only** wrapper around the current data snapshot.
	 * Any attempt to modify properties on this object will throw a RuntimeException.
	 *
	 * @return ReadOnlyObject
	 */
	public function getReadOnlyData(): ReadOnlyObject
	{
		return new ReadOnlyObject($this->getData());
	}

	/**
	 * Maps data keys for storage using the mapper strategy.
	 * If snake_case mapping is enabled, keys will be converted accordingly.
	 *
	 * @return object
	 */
	public function map(): object
	{
		$out = [];
		foreach ($this->data as $key => $val)
		{
			if (is_null($val) || in_array($key, $this->joinFields, true) || is_array($val))
			{
				continue;
			}

			/**
			 * This will check to block any aliased fields from being mapped.
			 */
			$alias = $this->alias[$key] ?? null;
			if ($alias && is_array($alias))
			{
				continue;
			}

			$keyMapped = $this->mapper->getMappedField($key);
			$out[$this->prepareKeyName($keyMapped)] = $val;
		}

		return (object)$out;
	}

	/**
	 * Prepares a key name using the mapper.
	 *
	 * @param string $key Key name.
	 * @return string
	 */
	protected function prepareKeyName(string $key): string
	{
		return $this->mapper->mapKey($key);
	}

	/**
	 * Converts rows of raw data to mapped objects.
	 *
	 * @param array $rows Array of rows.
	 * @return array
	 */
	public function convertRows(array $rows): array
	{
		if (empty($rows))
		{
			return [];
		}

		$formatted = [];
		foreach ($rows as $row)
		{
			$obj = new \stdClass();
			foreach ($this->data as $key => $val)
			{
				if (in_array($key, $this->fieldBlacklist, true))
				{
					continue;
				}

				$keyName = $this->prepareKeyName($key);
				$value = $row->{$keyName} ?? null;
				if ($this->nestedDataHelper->isNestedKey(($keyName)))
				{
					$value = $this->nestedDataHelper->getGroupedData($value);
				}

				$obj->{$key} = $value;
			}

			$formatted[] = $obj;
		}

		return $formatted;
	}
}