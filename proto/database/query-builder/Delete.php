<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * Delete
 *
 * This class handles delete queries.
 *
 * @package Proto\Database\QueryBuilder
 */
class Delete extends Query
{
	/**
	 * Renders the delete query.
	 *
	 * @return string The rendered SQL query.
	 */
	public function render(): string
	{
		$where = $this->getPropertyString($this->conditions, ' WHERE ', ' AND ');
		$orderBy = $this->getPropertyString($this->orderBy, ' ORDER BY ', ', ');
		return "DELETE FROM {$this->tableName} {$where}{$orderBy}{$this->limit};";
	}
}
