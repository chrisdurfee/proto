<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * Create
 *
 * This class will handle create queries.
 *
 * @package Proto\Database\QueryBuilder
 */
class Create extends Blueprint
{
    /**
     * @var array $fields
     */
    protected array $fields = [];

    /**
     * @var array $indices
     */
    protected array $indices = [];

    /**
     * @var array $foreign
     */
    protected array $foreign = [];

    /**
     * @var string $engine
     */
    protected string $engine = 'InnoDB';

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
     * This will set the table engine to MyISAM.
     *
     * @return self
     */
    public function myisam(): self
    {
        return $this->engine('MyISAM');
    }

    /**
     * This will insert data to the table.
     *
     * @param array|object ...$fields
     * @return self
     */
    public function create($data): self
    {
        foreach($data as $key => $value)
		{
			array_push($this->fields, "{$key} = {$value}");
		}

        return $this;
    }

    /**
     * This will add a field.
     *
     * @param string $name
     * @return CreateField
     */
    protected function createField(string $name): CreateField
    {
        $field = new CreateField($name);
        array_push($this->fields, $field);
        return $field;
    }

    /**
     * This will create an id field.
     *
     * @param int $length
     * @return CreateField
     */
    public function id(int $length = 11): CreateField
    {
        $field = $this->createField('id');
        $field->int($length)->primary()->autoIncrement();

        return $field;
    }

    protected function addDateTime(string $name): CreateField
    {
        $field = $this->createField($name);
        $field->dateTime();

        return $field;
    }

    /**
     * This will add a created at datetime.
     *
     * @return CreateField
     */
    public function createdAt(): CreateField
    {
        return $this->addDateTime('created_at');
    }

    /**
     * This will add an updated at datetime.
     *
     * @return CreateField
     */
    public function updatedAt(): CreateField
    {
        return $this->addDateTime('updated_at');
    }

    /**
     * This will add a deleted at datetime.
     *
     * @return CreateField
     */
    public function deletedAt(): CreateField
    {
        return $this->addDateTime('deleted_at');
    }

    /**
     * This will remove a field.
     *
     * @param object $field
     * @return self
     */
    public function removeField(object $field): self
    {
        $fields = $this->fields;
        $index = array_search($field, $fields);
        if($index !== false)
        {
            array_splice($fields, $index, 1);
        }

        return $this;
    }

    /**
     * This will check to pass the method to the child field.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments): mixed
    {
		$field = $this->createField($arguments[0]);
		$value = [$field, $method];
        if (!\is_callable($value))
        {
            $this->removeField($field);
            return null;
		}

        $arguments = array_slice($arguments, 1);
		return \call_user_func_array($value, $arguments);
    }

    /**
     * This will create an index.
     *
     * @param string $name
     * @return CreateIndex
     */
    public function index(string $name): CreateIndex
    {
        $index = new CreateIndex($name);
        array_push($this->indices, $index);
        return $index;
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
        array_push($this->foreign, $foreignKey);
        return $foreignKey;
    }

    protected function addCommaToString(string $text, array $array): string
    {
        $sql = '';
        if(count($array) < 1)
        {
            return $sql;
        }

        if($text)
        {
            $sql .= ', ';
        }
        $sql .= implode(', ', $array);
        return $sql;
    }

    /**
     * This will render the query.
     *
     * @return string
     */
    public function render(): string
    {
        $fields = implode(', ', $this->fields);
        $indices = $this->addCommaToString($fields, $this->indices);
        $foreign = $this->addCommaToString($fields, $this->foreign);

        return "CREATE TABLE IF NOT EXISTS {$this->tableName} (
                    {$fields}
                    {$indices}
                    {$foreign}
                )
                COLLATE='utf8mb4_unicode_ci'
                ENGINE={$this->engine};";
    }
}