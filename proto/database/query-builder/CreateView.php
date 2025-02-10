<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * CreateView
 *
 * Builds an SQL view definition.
 *
 * @package Proto\Database\QueryBuilder
 */
class CreateView extends Blueprint
{
	/**
	 * The select clause defining the view. Can be a Select builder or a raw SQL string.
	 *
	 * @var Select|string|null
	 */
	protected Select|string|null $selectClause = null;

	/**
	 * The name of the view.
	 *
	 * @var string
	 */
	protected string $viewName;

	/**
	 * Constructor.
	 *
	 * @param string $viewName The name of the view.
	 */
	public function __construct(string $viewName)
	{
		$this->viewName = $viewName;
	}

	/**
	 * Creates a Select query builder for the specified table.
	 *
	 * @param array|string $tableName The table name or list of table names.
	 * @param string|null $alias Optional alias for the table.
	 * @return Select
	 */
	public function table(array|string $tableName, ?string $alias = null): Select
	{
		$query = new Select($tableName, $alias);
		$this->selectClause = $query;
		return $query;
	}

	/**
	 * Sets a raw SQL query string as the select clause.
	 *
	 * @param string $query The SQL query string.
	 * @return void
	 */
	public function query(string $query): void
	{
		$this->selectClause = $query;
	}

	/**
	 * Renders the CREATE OR REPLACE VIEW SQL statement.
	 *
	 * @return string
	 */
	public function render(): string
	{
		$select = (string) $this->selectClause;
		return "CREATE OR REPLACE VIEW {$this->viewName} AS {$select}";
	}
}