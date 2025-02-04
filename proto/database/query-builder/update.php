<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * Update
 *
 * This class will handle update queries.
 *
 * @package Proto\Database\QueryBuilder
 */
class Update extends Query
{
    /**
     * @var array $fields
     */
    protected array $fields = [];

    /**
     * This will insert data to the table.
     *
     * @param array|object|string ...$fields
     * @return self
     */
    public function update(...$fields): self
    {
        $this->set(...$fields);

        return $this;
    }

    /**
     * This will set fields to be updated.
     *
     * @param mixed ...$fields
     * @return self
     */
    public function set(...$fields): self
    {
        if (count($fields) < 1)
        {
            return $this;
        }

        foreach ($fields as $row)
        {
            if (gettype($row) === 'string')
            {
                $fields = explode(',', $row);
                $this->fields = array_merge($this->fields, $fields);

                continue;
            }

            foreach ($row as $key => $value)
            {
                array_push($this->fields, "{$key} = {$value}");
            }
        }

        return $this;
    }

    /**
     * This will render the query.
     *
     * @return string
     */
    public function render(): string
    {
        $table = $this->getTableString();
        $joins = implode(' ', $this->joins);
        $fields = implode(', ', $this->fields);
        $where = $this->getPropertyString($this->conditions, ' WHERE ', ' AND ');
        $orderBy = $this->getPropertyString($this->orderBy, ' ORDER BY ', ', ');

        return "UPDATE
                    {$table}
                    {$joins}
                SET
                    {$fields}{$where}{$orderBy}{$this->limit};";
    }
}