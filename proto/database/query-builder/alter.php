<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * Alter
 *
 * This will handle the alter query.
 *
 * @package Proto\Database\QueryBuilder
 */
class Alter extends Blueprint
{
    /**
     * @var array $adding
     */
    protected array $adding = [];

    /**
     * @var array $changing
     */
    protected array $changing = [];

    /**
     * @var array $dropping
     */
    protected array $dropping = [];

    /**
     * @var array $updating
     */
    protected array $updating = [];

    /**
     * @var string $rename
     */
    protected string $rename = '';

    /**
     * @var array $fields
     */
    protected array $fields = [];

    /**
     * @var string|null $engine
     */
    protected ?string $engine = null;

    /**
     * This will rename the table.
     *
     * @param string $name
     * @return self
     */
    public function rename(string $name): self
    {
        $this->rename = 'RENAME TO ' . $name;

        return $this;
    }

    /**
     * This will add the engine.
     *
     * @param string $engine
     * @return self
     */
    public function engine(string $engine): self
    {
        $this->engine = $engine;

        return $this;
    }

    /**
     * This will insert data to the table.
     *
     * @param array|object ...$fields
     * @return self
     */
    public function create($data): self
    {
        foreach ($data as $key => $value)
		{
			array_push($this->fields, "{$key} = {$value}");
		}

        return $this;
    }

    /**
     * This will get the type.
     *
     * @param string $name
     * @param string|null $type
     * @return object
     */
    protected function getType(string $name, ?string $type = 'field'): object
    {
        return ($type !== 'index')? new CreateField($name) : new CreateIndex($name);
    }

    /**
     * This will add a field.
     *
     * @param string $name
     * @param string $type
     * @return object
     */
    public function add(string $name, ?string $type = 'field'): object
    {
        $add = $this->getType($name, $type);
        array_push($this->adding, $add);
        return $add;
    }

    /**
     * This will change a field.
     *
     * @param string $name
     * @param string $newName
     * @return object
     */
    public function change(string $name, string $newName): object
    {
        $change = $this->getType($name);
        $change->rename($newName);

        array_push($this->changing, $change);
        return $change;
    }

    /**
     * This will alter a field.
     *
     * @param string $name
     * @param string $type
     * @return object
     */
    public function alter(string $name, ?string $type = 'field'): object
    {
        $update = $this->getType($name, $type);
        array_push($this->updating, $update);
        return $update;
    }

    /**
     * This will create a foreign key.
     *
     * @param string $name
     * @return CreateForeignKey
     */
    public function foreign(string $field): CreateForeignKey
    {
        $foreignKey = new CreateForeignKey($field);
        array_push($this->adding, $foreignKey);
        return $foreignKey;
    }

    /**
     * This will drop a foreign key.
     *
     * @param string $keyName
     * @return self
     */
    public function dropForeignKey(string $keyName): self
    {
        array_push($this->dropping, 'FOREIGN KEY ' . $keyName);

        return $this;
    }

    /**
     * This will drop a field.
     *
     * @param string $fieldName
     * @return self
     */
    public function drop(string $fieldName): self
    {
        array_push($this->dropping, $fieldName);

        return $this;
    }

    /**
     * This will remove a field.
     *
     * @param object $field
     * @return self
     */
    public function removeField(object $field): self
    {
        $fields = $this->adding;
        $index = array_search($field, $fields);
        if ($index !== false)
        {
            array_splice($fields, $index, 1);
        }

        return $this;
    }

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
     * This wil get the adding fields.
     *
     * @return string
     */
    protected function adding(): string
    {
        return $this->getFieldString('ADD', $this->adding);
    }

    /**
     * This will create the fields sql.
     *
     * @param string $action
     * @param array|null $fields
     * @return string
     */
    protected function getFieldString(string $action, ?array $fields = []): string
    {
        $string = implode(', ' . $action . ' ', $fields);
        return (!empty($string))? $action . ' ' . $string : $string;
    }

    /**
     * This will render the query.
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
        $engine = (isset($this->engine))? " ENGINE={$this->engine}" : '';

        return "ALTER TABLE {$this->tableName}
                {$sql}{$engine};";
    }
}