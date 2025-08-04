<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * JoinBuilder
 *
 * This will handle the join builder.
 *
 * @package Proto\Database\QueryBuilder
 */
class JoinBuilder
{
    /**
     * @var array $joins
     */
    protected array $joins;

	/**
	 * This will construct the join builder.
     *
	 * @param array $joins
     * @return void
	 */
	public function __construct(array &$joins)
	{
        $this->joins = &$joins;
	}

    /**
     * This will get the table name.
     *
     * @param string|array $tableName
     * @return string
     */
    protected function getTableName($tableName): string
    {
        return (is_array($tableName))? '(' . $tableName[0] . ')' : $tableName;
    }

    /**
     * This will add a new join.
     *
     * @param string|array $tableName
     * @param string|null $alias
     * @return Join
     */
    protected function addJoin($tableName, ?string $alias = null): Join
    {
        $tableName = $this->getTableName($tableName);
        $join = new Join($tableName, $alias);
        array_push($this->joins, $join);
        return $join;
    }

    /**
     * This will add a join.
     *
     * @param string|array $tableName
     * @param string|null $alias
     * @return Join
     */
    public function join($tableName, ?string $alias = null): Join
    {
        return $this->addJoin($tableName, $alias);
    }

    /**
     * This will add a left join.
     *
     * @param string|array $tableName
     * @param string|null $alias
     * @return Join
     */
    public function left($tableName, ?string $alias = null): Join
    {
        $join = $this->addJoin($tableName, $alias);
        return $join->left();
    }

    /**
     * This will add a right join.
     *
     * @param string|array $tableName
     * @param string|null $alias
     * @return Join
     */
    public function right($tableName, ?string $alias = null): Join
    {
        $join = $this->addJoin($tableName, $alias);
        return $join->right();
    }

    /**
     * This will add an outer join.
     *
     * @param string|array $tableName
     * @param string|null $alias
     * @return Join
     */
    public function outer($tableName, ?string $alias = null): Join
    {
        $join = $this->addJoin($tableName, $alias);
        return $join->outer();
    }

    /**
     * This will add a cross join.
     *
     * @param string|array $tableName
     * @param string|null $alias
     * @return Join
     */
    public function cross($tableName, ?string $alias = null): Join
    {
        $join = $this->addJoin($tableName, $alias);
        return $join->cross();
    }
}