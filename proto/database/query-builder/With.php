<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * With
 *
 * This class creates a WITH statement (Common Table Expression).
 *
 * @package Proto\Database\QueryBuilder
 */
class With extends Template
{
	/**
	 * Indicates if the WITH statement is recursive.
	 *
	 * @var string|null
	 */
	protected ?string $recursive = null;

	/**
	 * The list of CTE tables.
	 *
	 * @var string[]
	 */
	protected array $cteTables = [];

	/**
	 * The query associated with the WITH statement.
	 *
	 * @var AdapterProxy|string
	 */
	protected AdapterProxy|string $query;

	/**
	 * Creates a Common Table Expression.
	 *
	 * @param string $cteName The name of the CTE.
	 * @param string $query The query for the CTE.
	 */
	public function __construct(string $cteName, string $query)
	{
		$this->cte($cteName, $query);
	}

	/**
	 * Adds a CTE.
	 *
	 * @param string $cteName The name of the CTE.
	 * @param string $query The query for the CTE.
	 * @return self
	 */
	public function cte(string $cteName, string $query): self
	{
		$cte = new Cte($cteName);
		$cte->query($query);
		$sql = (string)$cte;
		$this->cteTables[] = $sql;
		return $this;
	}

	/**
	 * Sets the query for the WITH statement.
	 *
	 * @param AdapterProxy|string $query The query.
	 * @return self
	 */
	public function query(AdapterProxy|string $query): self
	{
		$this->query = $query;
		return $this;
	}

	/**
	 * Creates a SELECT query builder.
	 *
	 * @param array|string $tableName The table name.
	 * @param string|null $alias The alias.
	 * @return AdapterProxy
	 */
	public function select(array|string $tableName, ?string $alias = null): AdapterProxy
	{
		$queryHandler = new QueryHandler($tableName, $alias);
		$select = $queryHandler->select();
		$this->query = $select;
		return $select;
	}

	/**
	 * Renders the WITH statement.
	 *
	 * @return string The rendered SQL query.
	 */
	public function render(): string
	{
		$cteTables = implode(',', $this->cteTables);
		$query = (string)$this->query;
		return "WITH {$cteTables} {$query}";
	}
}