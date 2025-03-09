<?php declare(strict_types=1);
namespace Proto\Models\Data;

use Proto\Utils\Strings;

/**
 * Class Data
 *
 * Base data class that uses a property mapper strategy and nested data helper.
 *
 * @package Proto\Models
 */
class Data
{
	/**
	 * Data storage.
	 *
	 * @var object
	 */
	protected object $data;

	/**
	 * @var array $alias Field aliases.
	 */
	protected array $alias = [];

	/**
	 * Join fields.
	 *
	 * @var array
	 */
	protected array $joinFields = [];

	/**
	 * Field blacklist.
	 *
	 * @var array
	 */
	protected array $fieldBlacklist = [];

	/**
	 * Property mapper.
	 *
	 * @var AbstractMapper
	 */
	protected AbstractMapper $mapper;

	/**
	 * Nested data helper.
	 *
	 * @var NestedDataHelper
	 */
	protected NestedDataHelper $nestedDataHelper;

	/**
	 * Data constructor.
	 *
	 * @param array $fields Fields to map.
	 * @param array $joins Join definitions.
	 * @param array $fieldBlacklist Fields to blacklist.
	 * @param boolean $snakeCase Use snake case.
	 */
	public function __construct(
		array $fields,
		array $joins = [],
		array $fieldBlacklist = [],
		bool $snakeCase = false
	) {
		$this->fieldBlacklist = $fieldBlacklist;

		/**
		 * This will determine the mapper type to use.
		 */
		$mapperType = ($snakeCase) ? 'snake' : 'default';
		$this->mapper = Mapper::factory($mapperType);

		/**
		 * Initialize the nested data helper.
		 */
		$this->nestedDataHelper = new NestedDataHelper();

		/**
		 * Set up the data.
		 */
		$this->setup($fields, $joins);
	}

	/**
	 * Set up data by initializing fields and join definitions.
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
	 * Initializes fields into the data object.
	 *
	 * @param array $fields Fields to add.
	 * @return void
	 */
	protected function setupFieldsToData(array $fields): void
	{
		if (count($fields) < 1)
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
	 * Initializes join fields into the data object.
	 *
	 * @param array $joins Join definitions.
	 * @return void
	 */
	protected function setupJoinsToData(array $joins): void
	{
		if (count($joins) < 1)
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
	 * Sets data values from an object.
	 *
	 * @param object $newData New data object.
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
	 * Sets data. Accepts either a key/value pair or an object.
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
			$value    = $args[1] ?? null;
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
	 * Returns the mapped data as an object.
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
	 * Maps data keys using the mapper strategy.
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
	 * Prepares the key name according to the mapper strategy.
	 *
	 * @param string $key Key name.
	 * @return string
	 */
	protected function prepareKeyName(string $key): string
	{
		return $this->mapper->mapKey($key);
	}

	/**
	 * Converts rows of data to mapped objects.
	 *
	 * @param array $rows Array of rows.
	 * @return array
	 */
	public function convertRows(array $rows): array
	{
		if (count($rows) < 1)
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
				$value   = $row->{$keyName} ?? null;
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