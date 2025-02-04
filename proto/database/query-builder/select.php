<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * Select
 *
 * This class will handle select queries.
 *
 * @package Proto\Database\QueryBuilder
 */
class Select extends FieldQuery
{
    /**
     * @var array $fields
     */
    protected array $fields = [];

    /**
     * @var string $distinct
     */
    protected string $distinct = '';

    /**
     * @var array $having
     */
    protected array $having = [];

    /**
     * @var array $groupBy
     */
    protected array $groupBy = [];

    /**
     * @var string $index
     */
    protected string $index = '';

    /**
     * @var array $unions
     */
    protected array $unions = [];

    /**
     * This will add columns to select.
     *
     * @param mixed ...$fields
     * @return self
     */
    public function select(...$fields): self
    {
        if (count($fields) < 1)
        {
            $fields[] = '*';
        }

        foreach ($fields as $row)
        {
            $this->addField($row, $this->alias);
        }

        return $this;
    }

    /**
     * This will add columns to select.
     *
     * @param mixed ...$fields
     * @return self
     */
    public function fields(...$fields): self
    {
        return $this->select(...$fields);
    }

    /**
     * This will force the index.
     *
     * @param string $index
     * @return self
     */
    public function forceIndex(string $index): self
    {
        $this->index = ' FORCE INDEX(' . $index . ') ';

        return $this;
    }

    /**
     * This will set the select to return distinct results.
     *
     * @return self
     */
    public function distinct(): self
    {
        $this->distinct = ' DISTINCT ';

        return $this;
    }

    /**
     * This will add group by fields.
     *
     * @param mixed ...$columns
     * @return self
     */
    public function groupBy(...$columns): self
    {
        if (count($columns) < 1)
        {
            return $this;
        }

        foreach ($columns as $row)
        {
            array_push($this->groupBy, $row);
        }

        return $this;
    }

    /**
     * This will add having conditions.
     *
     * @param array|string ...$having
     * @return self
     */
    public function having(...$having): self
    {
        if (count($having) < 1)
        {
            return $this;
        }

        foreach ($having as $row)
        {
            $condition = $this->getCompareString($row);
            array_push($this->having, $condition);
        }

        return $this;
    }

    /**
     * This will add a union.
     *
     * @param string ...$sql
     * @return self
     */
    public function union($sql): self
    {
        if (!$sql)
        {
            return $this;
        }

        array_push($this->unions, 'UNION ' . (string)$sql);

        return $this;
    }

    /**
     * This will add a union all.
     *
     * @param string ...$sql
     * @return self
     */
    public function unionAll($sql): self
    {
        if (!$sql)
        {
            return $this;
        }

        array_push($this->unions, 'UNION ALL ' . (string)$sql);

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

        $from = $this->getTableString();
        $joins = implode(' ', $this->joins);
        $unions = implode(' ', $this->unions);

        $where = $this->getPropertyString($this->conditions, ' WHERE ', ' AND ');
        $orderBy = $this->getPropertyString($this->orderBy, ' ORDER BY ', ', ');
        $groupBy = $this->getPropertyString($this->groupBy, ' GROUP BY ', ', ');
        $having = $this->getPropertyString($this->having, ' HAVING ', ' AND ');

        return "SELECT {$this->distinct}
                    {$fields}
                FROM
                    {$from}{$this->index}
                    {$joins}
                {$where}
                {$groupBy}
                {$having}
                {$orderBy}
                {$unions}
                {$this->limit}";
    }
}