<?php declare(strict_types=1);
namespace Proto\Models\Joins;

use Proto\Utils\Strings;

/**
 * OnHelper
 *
 * Processes ON conditions for SQL JOINs, allowing for flexible input formats.
 *
 * @package Proto\Models\Joins
 */
class OnHelper
{
	/**
	 * Constructor.
	 *
	 * @param string $thisAlias Alias/table name for the "left" side of comparisons (the join table itself).
	 * @param string $contextAlias Alias/table name for the "right" side of comparisons (the table being joined to).
	 * @param bool $isSnakeCase Whether to apply snake_case to unprepared column names.
	 */
	public function __construct(
		protected string $thisAlias,
		protected string $contextAlias,
		protected bool $isSnakeCase
	)
	{
	}

	/**
	 * Processes a single ON condition input.
	 *
	 * @param mixed $condition The raw condition (string, 2-element array, 3-element array).
	 * @return array|string|null Returns a structured array ['left'=>..., 'op'=>..., 'right'=>...] or a raw SQL string.
	 */
	public function process(mixed $condition): array|string|null
	{
		if (is_string($condition))
		{
			// Assume raw SQL
			return $condition;
		}

		if (is_array($condition) === false)
		{
			return null;
		}

		// This will check if we have an operator in the array
		$count = count($condition);
		if ($count === 2)
		{
			// No operator provided, treat as a 2-element condition
			return $this->processTwoElementCondition($condition);
		}

		// 3-element condition with operator
		return $this->processThreeElementCondition($condition);
	}

	/**
	 * Processes a 2-element array condition like ['this_col', 'context_col'].
	 *
	 * @param array $condition The 2-element array condition to process.
	 * @return array Structured array with 'left', 'op', and 'right' keys.
	 */
	protected function processTwoElementCondition(array $condition): array
	{
		$leftCol = $this->qualifyColumn($condition[0], $this->thisAlias);
		$rightCol = $this->qualifyColumn($condition[1], $this->contextAlias);
		return [$leftCol, '=', $rightCol];
	}

	/**
	 * Processes a 3-element array condition like ['this_col', 'op', 'value'].
	 *
	 * @param array $condition The 3-element array condition to process.
	 * @return array Structured array with 'left', 'op', and 'right' keys.
	 */
	protected function processThreeElementCondition(array $condition): array
	{
		$leftCol = $this->qualifyColumn($condition[0], $this->thisAlias);
		$operator = strtoupper(trim($condition[1]));
		$rawValue = $condition[2];
		$rightOperand = $rawValue;

		if (is_string($rawValue) && $this->isColumnModified($rawValue))
		{
			$rightOperand = $rawValue;
		}

		return [$leftCol, $operator, $rightOperand];
	}

	/**
	 * Checks if a column name is modified (i.e., contains special characters).
	 *
	 * @param string $column The column name to check.
	 * @return bool True if the column is modified, false otherwise.
	 */
	protected function isColumnModified(string $column): bool
	{
		return str_contains($column, '.') || str_contains($column, '(') || str_contains($column, ' ');
	}

	/**
	 * Prepare a column name (apply snake_case if enabled).
	 *
	 * @param string $column The column name to prepare.
	 * @return string The prepared column name.
	 */
	protected function prepareColumnName(string $column): string
	{
		if ($this->isColumnModified($column))
		{
			return $column;
		}

		return $this->isSnakeCase ? Strings::snakeCase($column) : $column;
	}

	/**
	 * Qualifies a column name with its table alias, if not already qualified.
	 *
	 * @param string $column The column name to qualify.
	 * @param string $defaultAlias The default alias to use if the column is unqualified.
	 * @return string The fully qualified column name.
	 */
	protected function qualifyColumn(string $column, string $defaultAlias): string
	{
		if (str_contains($column, '.'))
		{
			return $column;
		}

		$preparedName = $this->prepareColumnName($column);
		return "{$defaultAlias}.{$preparedName}";
	}
}