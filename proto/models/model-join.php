<?php declare(strict_types=1);
namespace Proto\Models;

use Proto\Utils\Strings;

/**
 * ModelJoin
 *
 * This will create a model join object.
 *
 * @package Proto\Models
 */
class ModelJoin
{
    /**
     * @var string $type
     */
    protected string $type = 'JOIN';

    /**
     * @var string $using
     */
    protected string $using;

    /**
     * @var array $on
     */
    protected array $on = [];

    /**
     * @var array $fields
     */
    protected array $fields = [];

    /**
     * @var string|array $tableName
     */
	protected string|array $tableName;

    /**
     * @var string|null $alias
     */
    protected ?string $alias;

    /**
     * @var string $as
     */
    protected string $as;

    /**
     * @var string|array $joinTableName
     */
    protected string|array $joinTableName;

    /**
     * @var string|null $joinAlias
     */
    protected ?string $joinAlias;

    /**
     * @var JoinBuilder $builder
     */
    protected JoinBuilder $builder;

    /**
     * @var bool $multiple
     */
    protected bool $multiple = false;

    /**
     * @var bool $isSnakeCase
     */
    protected bool $isSnakeCase = true;

    /**
     * @var ModelJoin|null $mulitpleJoin
     */
    protected ?ModelJoin $mulitpleJoin = null;

	/**
	 * This will set up the join.
     *
	 * @param object $builder
	 * @param string|array $tableName
	 * @param string|null $alias
     * @return void
	 */
	public function __construct(
        object &$builder,
        mixed $tableName,
        string $alias = null,
        bool $isSnakeCase = true
    )
	{
        $this->tableName = $tableName;
        $this->builder = $builder;
        $this->alias = $alias;
        $this->isSnakeCase = $isSnakeCase;

        $this->setupJoinSettings();
	}

    /**
     * This will setup the koin table settings.
     *
     * @return void
     */
    protected function setupJoinSettings(): void
    {
        $joinSettings = $this->builder->getTableSettings();
        $this->joinTableName = $joinSettings->tableName;
        $this->joinAlias = $joinSettings->alias;
    }

    /**
     * This will override the join table name.
     *
     * @param mixed $tableName
     * @param string|null $alias
     * @return void
     */
    protected function references(mixed $tableName, ?string $alias = null): void
    {
        $this->joinTableName = $tableName;
        $this->joinAlias = $alias;
    }

    /**
     * This will get the table name.
     *
     * @return string|array
     */
    public function getTableName(): mixed
    {
        return $this->tableName;
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
     * This will set the join as a multiple.
     *
     * @param string|array|null $tableName
     * @param string|null
     * @return ModelJoin|self
     */
    public function multiple(mixed $tableName = null, ?string $alias = null)
    {
        $this->multiple = true;
        if (empty($tableName))
        {
            return $this;
        }

        $join = new ModelJoin($this->builder, $tableName, $alias);
        $join->references($this->tableName, $this->alias);
        return ($this->mulitpleJoin = $join);
    }

    /**
     * This will get the multiple join.
     *
     * @return ModelJoin|null
     */
    public function getMultipleJoin(): ?ModelJoin
    {
        return $this->mulitpleJoin;
    }

    /**
     * This will check if the join is a multiple.
     *
     * @return bool
     */
    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * This will add the type.
     *
     * @param string $type
     * @return self
     */
    public function addType(string $type = 'JOIN'): self
    {
        $this->type = \strtoupper($type);

        return $this;
    }

    /**
     * This will get the type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
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
     * This will set the as for alias.
     *
     * @param string $as
     * @return self
     */
    public function as(string $as): self
    {
        $this->as = $as;

        return $this;
    }

    /**
     * This will get the as.
     *
     * @return mixed
     */
    public function getAs(): mixed
    {
        return $this->as ?? $this->tableName;
    }

    /**
     * This will get a new join builder to build joins on this table.
     *
     * @return JoinBuilder
     */
    public function join(?string $modelClassName = null): JoinBuilder
    {
        $builder = $this->builder->link($this->tableName, $this->alias);

        if (isset($modelClassName))
        {
            $builder->setModelClassName($modelClassName);
        }
        return $builder;
    }

    /**
     * This will add the fields.
     *
     * @param mixed ...$fields
     * @return self
     */
    public function fields(...$fields): self
    {
        if (count($fields) < 1)
        {
            return $this;
        }

        foreach ($fields as $row)
        {
            array_push($this->fields, $row);
        }

        return $this;
    }

    /**
     * This will get the join fields.
     *
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
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
     * This will get the using field.
     *
     * @return string|null
     */
    public function getUsing(): ?string
    {
        return $this->using;
    }

    /**
     * This will get the on.
     *
     * @return array
     */
    public function getOn(): array
    {
        return $this->on;
    }

    /**
     * This will decamelize a string.
     *
     * @param string $field
     * @return string
     */
    protected static function decamelize(string $field): string
    {
        return Strings::snakeCase($field);
    }

    /**
     * This will prepare the on column.
     *
     * @param string $column
     * @return string
     */
    protected function prepareOnColumn(string $column): string
    {
        if ($this->isSnakeCase === false)
        {
            return $column;
        }

        return self::decamelize($column);
    }

    /**
     * This will add the on conditions.
     *
     * @param array|string ...$on
     * @return self
     */
    public function on(...$on): self
    {
        if (count($on) < 1)
        {
            return $this;
        }

        $alias = $this->alias ?? $this->tableName;
        $joinAlias = $this->joinAlias ?? $this->joinTableName;

        /**
         * This will clear the on before adding a new on clause. Multiple
         * clauses should be added as additional params.
         */
        $this->on = [];

        foreach ($on as $row)
        {
            if (is_array($row))
            {
                // this will allow raw sql to be set as a field
                $count = count($row);
                if ($count > 1)
                {
                    if ($count === 2)
                    {
                        $row = [$joinAlias . '.' . $this->prepareOnColumn($row[0]), $alias . '.' . $this->prepareOnColumn($row[1])];
                    }
                    else
                    {
                        $row = [$joinAlias . '.' . $this->prepareOnColumn($row[0]), $row[1], $alias . '.' . $this->prepareOnColumn($row[2])];
                    }
                }
            }

            array_push($this->on, $row);
        }

        return $this;
    }
}