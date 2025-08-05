<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * Alter
 *
 * Handles the ALTER TABLE query.
 *
 * @package Proto\Database\QueryBuilder
 */
class Alter extends Blueprint
{
	/**
	 * Array of fields to add.
	 *
	 * @var array
	 */
	protected array $adding = [];

	/**
	 * Array of fields to change.
	 *
	 * @var array
	 */
	protected array $changing = [];

	/**
	 * Array of fields to drop.
	 *
	 * @var array
	 */
	protected array $dropping = [];

	/**
	 * Array of fields to modify.
	 *
	 * @var array
	 */
	protected array $updating = [];

	/**
	 * Table rename clause.
	 *
	 * @var string
	 */
	protected string $rename = '';

	/**
	 * Array of field data for creation.
	 *
	 * @var array
	 */
	protected array $fields = [];

	/**
	 * Table engine.
	 *
	 * @var string|null
	 */
	protected ?string $engine = null;

	/**
	 * Renames the table.
	 *
	 * @param string $name The new table name.
	 * @return self
	 */
	public function rename(string $name): self
	{
		$this->rename = 'RENAME TO ' . $name;
		return $this;
	}

	/**
	 * Sets the table engine.
	 *
	 * @param string $engine The engine name.
	 * @return self
	 */
	public function engine(string $engine): self
	{
		$this->engine = $engine;
		return $this;
	}

	/**
	 * Inserts data to the table.
	 *
	 * @param array $data Associative array of field => value pairs.
	 * @return self
	 */
	public function create(array $data): self
	{
		foreach ($data as $key => $value)
		{
			$this->fields[] = "{$key} = {$value}";
		}
		return $this;
	}

	/**
	 * Returns a new field or index object based on type.
	 *
	 * @param string $name The field name.
	 * @param string|null $type The type, default is 'field'.
	 * @return object
	 */
	protected function getType(string $name, ?string $type = 'field'): object
	{
		return ($type !== 'index') ? new CreateField($name) : new CreateIndex($name);
	}

	/**
	 * Adds a field.
	 *
	 * @param string $name The field name.
	 * @param string|null $type The type, default is 'field'.
	 * @return object
	 */
	public function add(string $name, ?string $type = 'field'): object
	{
		$add = $this->getType($name, $type);
		$this->adding[] = $add;
		return $add;
	}

	/**
	 * Changes a field.
	 *
	 * @param string $name The current field name.
	 * @param string $newName The new field name.
	 * @return object
	 */
	public function change(string $name, string $newName): object
	{
		$change = $this->getType($name);
		$change->rename($newName);
		$this->changing[] = $change;
		return $change;
	}

	/**
	 * Alters a field.
	 *
	 * @param string $name The field name.
	 * @param string|null $type The type, default is 'field'.
	 * @return object
	 */
	public function alter(string $name, ?string $type = 'field'): object
	{
		$update = $this->getType($name, $type);
		$this->updating[] = $update;
		return $update;
	}

	/**
	 * Creates a foreign key.
	 *
	 * @param string $field The local field for the foreign key.
	 * @return CreateForeignKey
	 */
	public function foreign(string $field): CreateForeignKey
	{
		$foreignKey = new CreateForeignKey($field);
		$this->adding[] = $foreignKey;
		return $foreignKey;
	}

	/**
	 * Drops a foreign key.
	 *
	 * @param string $keyName The name of the foreign key.
	 * @return self
	 */
	public function dropForeignKey(string $keyName): self
	{
		$this->dropping[] = 'FOREIGN KEY ' . $keyName;
		return $this;
	}

	/**
	 * Drops a field.
	 *
	 * @param string $fieldName The name of the field.
	 * @return self
	 */
	public function drop(string $fieldName): self
	{
		$this->dropping[] = $fieldName;
		return $this;
	}

	/**
	 * Removes a field from the adding list.
	 *
	 * @param object $field The field object to remove.
	 * @return self
	 */
	public function removeField(object $field): self
	{
		$index = array_search($field, $this->adding);
		if ($index !== false)
		{
			array_splice($this->adding, $index, 1);
		}
		return $this;
	}

	/**
	 * Adds a comma-separated string based on an action and array.
	 *
	 * @param string $text The existing SQL text.
	 * @param string $action The action keyword.
	 * @param array $array The array of clauses.
	 * @return string
	 */
	protected function addCommaToString(string $text, string $action, array $array): string
	{
		$sql = '';
		if (count($array) < 1)
		{
			return $sql;
		}
		if ($text)
		{
			$sql .= ', ';
		}
		$sql .= $this->getFieldString($action, $array);
		return $sql;
	}

	/**
	 * Gets the adding fields SQL.
	 *
	 * @return string
	 */
	protected function adding(): string
	{
		return $this->getFieldString('ADD', $this->adding);
	}

	/**
	 * Creates a string for field clauses.
	 *
	 * @param string $action The action keyword.
	 * @param array|null $fields The list of fields.
	 * @return string
	 */
	protected function getFieldString(string $action, ?array $fields = []): string
	{
		$string = implode(', ' . $action . ' ', $fields);
		return (!empty($string)) ? $action . ' ' . $string : $string;
	}

	/**
	 * Renders the ALTER TABLE query.
	 *
	 * @return string
	 */
	public function render(): string
	{
		$sql = $this->rename;
		$sql .= $this->addCommaToString($sql, 'ADD', $this->adding);
		$sql .= $this->addCommaToString($sql, 'CHANGE', $this->changing);
		$sql .= $this->addCommaToString($sql, 'DROP', $this->dropping);
		$sql .= $this->addCommaToString($sql, 'MODIFY', $this->updating);
		$engine = isset($this->engine) ? " ENGINE={$this->engine}" : '';
		return "ALTER TABLE {$this->tableName} {$sql}{$engine};";
	}
}