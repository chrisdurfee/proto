<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * Join
 *
 * This will handle the join query.
 *
 * @package Proto\Database\QueryBuilder
 */
class Join extends FieldQuery
{
    /**
     * @var string $type
     */
    protected string $type = 'JOIN';

    /**
     * @var string $using
     */
    protected string $using = '';

    /**
     * @var array $on
     */
    protected array $on = [];

    /**
     * This will add the type.
     *
     * @param string $type
     * @return self
     */
    public function addType(string $type): self
    {
        $this->type = \strtoupper($type);

        return $this;
    }

    /**
     * This will add a left join.
     *
     * @return self
     */
    public function left(): self
    {
        $this->addType('LEFT JOIN');

        return $this;
    }

    /**
     * This will add a right join.
     *
     * @return self
     */
    public function right(): self
    {
        $this->addType('RIGHT JOIN');

        return $this;
    }

    /**
     * This will add a outer join.
     *
     * @return self
     */
    public function outer(): self
    {
        $this->addType('OUTER JOIN');

        return $this;
    }

    /**
     * This will add a cross join.
     *
     * @return self
     */
    public function cross(): self
    {
        $this->addType('CROSS JOIN');

        return $this;
    }

    /**
     * This will add columns to join.
     *
     * @param mixed ...$fields
     * @return self
     */
    public function fields(...$fields): self
    {
        if(count($fields) < 1)
        {
            return $this;
        }

        foreach($fields as $row)
        {
            $this->addField($row, $this->alias);
        }

        return $this;
    }

    /**
     * This will add the using field.
     *
     * @param string $field
     * @return self
     */
    public function using(string $field): self
    {
        $this->using = 'USING(' . $field . ')';

        return $this;
    }

    /**
     * This will add the on conditions.
     *
     * @param array|string ...$on
     * @return self
     */
    public function on(...$on): self
    {
        if(count($on) < 1)
        {
            return $this;
        }

        foreach($on as $row)
        {
            $condition = $this->getCompareString($row);
            array_push($this->on, $condition);
        }

        return $this;
    }

    /**
     * This will get the table bind.
     *
     * @return string
     */
    protected function getBind(): string
    {
        $using = $this->using;
        if(!empty($using))
        {
            return $using;
        }

        $on = implode(' AND ', $this->on);
        return "ON {$on}";
    }

    /**
     * This will render the sql.
     *
     * @return string
     */
    public function render(): string
    {
        $on = $this->getBind();
        $alias = !empty($this->alias)? ' AS ' . $this->alias : '';

        return "{$this->type} {$this->tableName}{$alias} {$on}";
    }
}
