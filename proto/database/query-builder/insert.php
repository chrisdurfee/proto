<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * Insert
 *
 * This will handle the insert query.
 *
 * @package Proto\Database\QueryBuilder
 */
class Insert extends Query
{
    /**
     * @var array $fields
     */
    protected array $fields = [];

    /**
     * @var array $values
     */
    protected array $values = [];

    /**
     * @var array $conditions
     */
    protected array $conditions = [];

    /**
     * @var string $onDuplicate
     */
    protected string $onDuplicate = '';

    /**
     * This will insert data to the table.
     *
     * @param array|object|null $data
     * @return self
     */
    public function insert($data = null): self
    {
        if (isset($data) === false)
        {
            return $this;
        }

        $params = $this->createParamsFromData($data);
		$this->fields = $params->cols;
		$this->values = $params->values;

        return $this;
    }

    /**
     * This will set the fields.
     *
     * @param array $fields
     * @return self
     */
    public function fields(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * This will set the values.
     *
     * @param array $values
     * @return self
     */
    public function values(array $values): self
    {
        $this->values = $values;
        return $this;
    }

    /**
     * This will create placeholders.
     *
     * @param array $values
     * @return array
     */
    protected function createPlaceholders(array $values): array
    {
        return array_fill(0, count($values), '?');
    }

    /**
     * This will bind the fields to params.
     *
     * @param array|object $data
     * @param array $params
     * @return self
     */
    public function bind($data, &$params = []): self
    {
        if (isset($data) === false)
        {
            return $this;
        }

        $dataParams = $this->createParamsFromData($data);
		$this->fields = $dataParams->cols;
		$this->values = $this->createPlaceholders($dataParams->values);
        $params = array_merge($params, $dataParams->values);

        return $this;
    }

    /**
     * This will create the duplicate field string.
     *
     * @param string $key
     * @param string $value
     * @return string
     */
    protected function getDuplicateField(string $key, string $value): string
    {
        return "{$key} => VALUES({$value})";
    }

    /**
     * This will add an on duplicate.
     *
     * @param array $fields
     * @return self
     */
    public function onDuplicate(array $updateFields): self
    {
        if (count($updateFields) === 0)
        {
            return $this;
        }

        $fields = [];
        foreach ($updateFields as $key => $value)
        {
            $fields[] = $this->getDuplicateField($key, $value);
        }

        $this->onDuplicate = ' ON DUPLICATE KEY UPDATE ' . implode(', ', $fields);
        return $this;
    }

    /**
     * This will render the query.
     *
     * @return string
     */
    public function render(): string
    {
        $fields = implode(', ', $this->fields);
        $values = implode(', ', $this->values);
        $duplicate = $this->onDuplicate;

        return "INSERT INTO {$this->tableName}
                    ({$fields})
                VALUES
                    ({$values}){$duplicate};";
    }
}