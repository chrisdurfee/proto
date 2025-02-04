<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * Replace
 *
 * This class will handle replace queries.
 *
 * @package Proto\Database\QueryBuilder
 */
class Replace extends Insert
{
    /**
     * This will replace data to the table.
     *
     * @param array|object|null $data
     * @return self
     */
    public function replace($data = null): self
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
     * This will render the query.
     *
     * @return string
     */
    public function render(): string
    {
        $fields = implode(', ', $this->fields);
        $values = implode(', ', $this->values);

        return "REPLACE INTO {$this->tableName}
                    ({$fields})
                VALUES
                    ({$values});";
    }
}