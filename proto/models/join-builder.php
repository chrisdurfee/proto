<?php declare(strict_types=1);
namespace Proto\Models;

/**
 * JoinBuilder
 *
 * This will build the model joins.
 *
 * @package Proto\Models
 */
class JoinBuilder
{
    /**
     * @var string $tableName
     */
	protected string $tableName;

    /**
     * @var string|null $alias
     */
    protected string|null $alias;

    /**
     * @var bool $isSnakeCase
     */
    protected bool $isSnakeCase = true;

    /**
     * @var string|null $modelClassName
     */
    protected string|null $modelClassName;

    /**
     * @var array $joins
     */
    protected array $joins;

	/**
	 *
	 * @param array $joins
	 * @param string|array $tableName
	 * @param string|null $alias
     * @return void
	 */
	public function __construct(
        array &$joins,
        $tableName,
        ?string $alias = null,
        bool $isSnakeCase = true
    )
	{
        $this->tableName = $tableName;
        $this->joins = &$joins;
        $this->alias = $alias;
        $this->isSnakeCase = $isSnakeCase;
	}

    /**
     * This will get the table settings.
     *
     * @return object
     */
    public function getTableSettings(): object
    {
        return (object)[
            'tableName' => $this->tableName,
            'alias' => $this->alias
        ];
    }

    /**
     * This will set the join model class name.
     *
     * @param string $modelClassName
     * @return self
     */
    public function setModelClassName(string $modelClassName): self
    {
        $this->modelClassName = $modelClassName;
        return $this;
    }

    /**
     * This will get the model id column name.
     *
     * @return string
     */
    public function getModelIdName(): string
    {
        return "{$this->modelClassName}Id";
    }

    /**
     * This will add a new join.
     *
     * @param string|array $tableName
     * @param string|null $alias
     * @return ModelJoin
     */
    protected function addJoin($tableName, ?string $alias = null): ModelJoin
    {
       $join = new ModelJoin($this, $tableName, $alias, $this->isSnakeCase);
       array_push($this->joins, $join);
       return $join;
    }

    /**
     * This will add a join.
     *
     * @param string|array $tableName
     * @param string|null $alias
     * @return ModelJoin
     */
    public function join($tableName, ?string $alias = null): ModelJoin
    {
        return $this->addJoin($tableName, $alias);
    }

    /**
     * This will add a left join.
     *
     * @param string|array $tableName
     * @param string|null $alias
     * @return ModelJoin
     */
    public function left($tableName, ?string $alias = null): ModelJoin
    {
        $join = $this->addJoin($tableName, $alias);
        return $join->left();
    }

    /**
     * This will add a right join.
     *
     * @param string|array $tableName
     * @param string|null $alias
     * @return ModelJoin
     */
    public function right($tableName, ?string $alias = null): ModelJoin
    {
        $join = $this->addJoin($tableName, $alias);
        return $join->right();
    }

    /**
     * This will add an outer join.
     *
     * @param string|array $tableName
     * @param string|null $alias
     * @return ModelJoin
     */
    public function outer($tableName, ?string $alias = null): ModelJoin
    {
        $join = $this->addJoin($tableName, $alias);
        return $join->outer();
    }

    /**
     * This will add a cross join.
     *
     * @param string|array $tableName
     * @param string|null $alias
     * @return ModelJoin
     */
    public function cross($tableName, ?string $alias = null): ModelJoin
    {
        $join = $this->addJoin($tableName, $alias);
        return $join->cross();
    }

    /**
     * This will get a new join builder to create more joins.
     *
     * @param string|array $tableName
     * @param string|null $alias
     * @return JoinBuilder
     */
    public function link(mixed $tableName, ?string $alias = null): JoinBuilder
    {
        return new JoinBuilder($this->joins, $tableName, $alias, $this->isSnakeCase);
    }
}