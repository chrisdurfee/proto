<?php declare(strict_types=1);
namespace Proto\Models\Data;

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
	 * @param array $joins Join definitions.
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
			if ($join->isMultiple())
			{
				$key = Strings::camelCase($join->getAs());
				$this->setDataField($key, []);
				continue;
			}

			$joiningFields = $join->getFields() ?? false;
			if (!$joiningFields)
			{
				continue;
			}

			foreach ($joiningFields as $field)
			{
				$key = $this->checkAliasField($field);
				$this->joinFields[] = $key;
				$this->setDataField($key, null);
			}
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

			if (is_array($this->data->{$keyMapped}))
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
				if (is_array($val))
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