<?php declare(strict_types=1);

namespace Proto\Database\QueryBuilder;

/**
 * Drop
 *
 * This class handles drop queries.
 *
 * @package Proto\Database\QueryBuilder
 */
class Drop extends Query
{
	/**
	 * The type of drop (e.g., TABLE, DATABASE).
	 *
	 * @var string
	 */
	protected string $type = 'TABLE';

	/**
	 * The name of the table to drop.
	 *
	 * @var string
	 */
	protected string $tableName;

	/**
	 * Sets the drop type.
	 *
	 * @param string $type The drop type.
	 * @return void
	 */
	public function type(string $type): void
	{
		if (empty($type))
		{
			return;
		}
		$this->type = strtoupper($type);
	}

	/**
	 * Renders the drop query.
	 *
	 * @return string The rendered SQL query.
	 */
	public function render(): string
	{
		return "DROP {$this->type} {$this->tableName};";
	}
}