<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

use Proto\Database\Adapters\SQL\Mysql\MysqliBindTrait;

/**
 * Query
 *
 * This class will be the base class for all of the
 * queries.
 *
 * @package Proto\Database\QueryBuilder
 * @abstract
 */
abstract class Query extends Template
{
	use MysqliBindTrait;

    /**
     * @var array $conditions
     */
    protected array $conditions = [];

    /**
     * @var string $tableName
     */
    protected string $tableName;

    /**
     * @var string|null $alias
     */
    protected ?String $alias = null;

    /**
     * @var array $joins
     */
    protected array $joins = [];

    /**
     * @var array $fields
     */
    protected array $fields = [];

    /**
     * @var array $orderBy
     */
    protected array $orderBy = [];

    /**
     * @var string $limit
     */
    protected string $limit = '';

    /**
     * This will construct the query.
     *
     * @param string $tableName
     * @param string|null $alias
     * @return void
     */
    public function __construct(string $tableName, ?string $alias = null)
    {
        $this->tableName = $tableName;
        $this->alias = $alias ?? $tableName;
    }

    /**
     * This will get the alias.
     *
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * This will add a field.
     *
     * @param mixed $row
     * @param string $alias
     * @return void
     */
    protected function addField(mixed $row, string $alias): void
    {
        if (is_array($row))
        {
            // this will allow raw sql to be set as a field
            if (count($row) < 2)
            {
                $column = $row[0];
            }
            else if (\is_array($row[0]))
            {
                $column = $row[0][0] . ' AS ' . $row[1];
            }
            else
            {
                $row = $row[0] . ' AS ' . $row[1];
                $column = $alias . '.' . $row;
            }
        }
        else
        {
            $column = $alias . '.' . $row;
        }

        array_push($this->fields, $column);
    }

    /**
     * This will get the compare value.
     *
     * @param string|array $row
     * @return string
     */
    protected function getCompareString($row): string
    {
        if (is_array($row) === false)
        {
            return $row;
        }

        $length = count($row);
        switch ($length)
        {
            case 3:
                $value = \implode(' ', $row);
                break;
            case 2:
                $value = $row[0] . ' = ' . $row[1];
                break;
            default:
                $value = $row[0];
        }
        return $value;
    }

    /**
     * This will get the join table name.
     *
     * @param array $join
     * @return string
     */
    protected function getJoinTableName(array $join): string
    {
        return (is_array($join['table']))? '(' . $join['table'][0] . ')' : $join['table'];
    }

    /**
     * This will create a join builder to build joins in a callBack.
     *
     * @param callable $callBack
     * @return void
     */
    protected function joinBuilder(callable $callBack): void
    {
        $joins = [];
        $builder = new JoinBuilder($joins);

        \call_user_func($callBack, $builder);

        foreach ($joins as $join)
        {
            $fields = $join->getFields();
            if ($fields)
            {
                $alias = $join->getAlias();
                $this->addJoinFields($fields, $alias);
            }

            array_push($this->joins, (string)$join);
        }
    }

    /**
     * This will add a join.
     *
     * @param array|callable $join
     * @return self
     */
    public function join($join): self
    {
        if (is_callable($join) === true)
        {
            $this->joinBuilder($join);
            return $this;
        }

        if (count($join) < 1)
        {
            return $this;
        }

        $type = isset($join['type'])? strtoupper($join['type']) : 'INNER JOIN';

        $tableSql = $this->getJoinTableName($join);

        $tableAlias = (empty($join['alias']))? '' : 'AS ' . $join['alias'];
        $alias = $join['alias'] ?? $tableSql;

        if (isset($join['using']))
        {
            $on = ' ' . $join['using'];
        }
        else
        {
            $on = (!empty($join['on']))? ' ON ' . $this->getOnString($join['on']) . ' ' : '';
        }

        $sql = ' ' . $type . ' ' . $tableSql . ' ' . $alias . $on;
        array_push($this->joins, $sql);

        $fields = $join['fields'] ?? null;
        if ($fields)
        {
            $this->addJoinFields($fields, $alias);
        }

        return $this;
    }

    /**
     * This will add a left join.
     *
     * @param array $join
     * @return self
     */
    public function leftJoin(array $join): self
    {
        $join['type'] = 'left join';
        return $this->join($join);
    }

    /**
     * This will add a right join.
     *
     * @param array $join
     * @return self
     */
    public function rightJoin(array $join): self
    {
        $join['type'] = 'right join';
        return $this->join($join);
    }

    /**
     * This will add an outer join.
     *
     * @param array $join
     * @return self
     */
    public function outerJoin(array $join): self
    {
        $join['type'] = 'outer join';
        return $this->join($join);
    }

    /**
     * This will add a cross join.
     *
     * @param array $join
     * @return self
     */
    public function crossJoin(array $join): self
    {
        $join['type'] = 'cross join';
        return $this->join($join);
    }

    /**
     * This will add multiple joins.
     *
     * @param array $joins
     * @return self
     */
    public function joins(array $joins): self
    {
        if (count($joins) < 1)
        {
            return $this;
        }

        foreach ($joins as $join)
        {
            $this->join($join);
        }

        return $this;
    }

    /**
     * This will get the table string.
     *
     * @return string
     */
    protected function getTableString(): string
    {
        return ($this->alias === $this->tableName)? $this->tableName : $this->tableName . ' AS ' . $this->alias;
    }

    /**
     * This will get the join on string.
     *
     * @param array|null $on
     * @return string
     */
    protected function getOnString(?array $on): string
    {
        if (!$on)
        {
            return '';
        }

        $on = array_map([$this, 'getCompareString'], $on);
        return \implode(' AND ', $on);
    }

    protected function addJoinFields(?array $fields = null, ?string $alias = null)
    {
        if (!$fields || count($fields) < 1)
        {
            return;
        }

        foreach ($fields as $row)
        {
            //This will conver prealiased as raw SQL
            $row = (gettype($row) === 'string' && strpos($row, '.'))? [$row] : $row;
            $this->addField($row, $alias);
        }
    }

    /**
     * This will get a string for the property.
     *
     * @param array $property
     * @param string $propertyText
     * @param string $glueText
     * @return string
     */
    protected function getPropertyString(array $property, string $propertyText, string $glueText): string
    {
        return (count($property) < 1)? '' : ' ' . $propertyText . '  ' . implode($glueText, $property);
    }

    /**
     * This will add where conditions.
     *
     * @param array|string ...$where
     * @return self
     */
    public function where(...$where): self
    {
        if (count($where) < 1)
        {
            return $this;
        }

        foreach ($where as $row)
        {
            $condition = $this->getCompareString($row);
            array_push($this->conditions, $condition);
        }

        return $this;
    }

    /**
     * This will add order by columns.
     *
     * @param mixed ...$columns
     * @return self
     */
    public function orderBy(...$columns): self
    {
        if (count($columns) < 1)
        {
            return $this;
        }

        foreach ($columns as $row)
        {
            if (is_array($row) === false)
            {
                $orderBy = $row;
            }
            else
            {
                $orderBy = $row[0] . ' ' . strtoupper($row[1]);
            }

            array_push($this->orderBy, $orderBy);
        }

        return $this;
    }

    /**
     * This will add a in clause to the conditions.
     *
     * @param string $columnName
     * @param array $fields
     * @return self
     */
    public function in(string $columnName, array $fields): self
    {
        $placeholders = array_fill(0, count($fields), '?');
        $placeholders = implode(',' , $placeholders);
        $condition = "{$columnName} IN ({$placeholders})";

        array_push($this->conditions, $condition);

        return $this;
    }

    /**
     * This will add a limit.
     *
     * @param int|null $offset
     * @param int|null $count
     * @return self
     */
    public function limit(?int $offset = null, ?int $count = null): self
	{
		if (\is_null($offset))
		{
			return $this;
		}

        $int = (int)$offset;
        if (!\is_numeric($int))
        {
            return $this;
        }

        $this->limit = " LIMIT " . $int;

        if (!\is_null($count))
        {
            $int = (int)$count;
            if (!\is_numeric($int))
            {
                return $this;
            }

            $this->limit .= ", " . $int;
        }

		return $this;
	}
}