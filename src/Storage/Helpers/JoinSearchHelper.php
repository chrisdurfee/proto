<?php declare(strict_types=1);
namespace Proto\Storage\Helpers;

use Proto\Models\Joins\ModelJoin;
use Proto\Utils\Strings;

/**
 * JoinSearchHelper
 *
 * Generates EXISTS subqueries to search within joined/nested data.
 *
 * @package Proto\Storage\Helpers
 */
class JoinSearchHelper
{
	/**
	 * Build an EXISTS subquery to search within a join.
	 *
	 * @param string $joinAlias The alias of the join to search in (e.g., 'participants')
	 * @param array $searchFields Field names to search (e.g., ['firstName', 'lastName'])
	 * @param string $searchValue The search value (will be wrapped with % for LIKE)
	 * @param array $joins All compiled joins from the model
	 * @param string $parentAlias The parent table alias (e.g., 'cp')
	 * @param bool $isSnakeCase Whether to convert field names to snake_case
	 * @return array|null ['sql' => string, 'params' => array] or null if join not found
	 */
	public static function buildSearchSubquery(
		string $joinAlias,
		array $searchFields,
		string $searchValue,
		array $joins,
		string $parentAlias,
		bool $isSnakeCase = true
	): ?array
	{
		// Find the target join by alias
		$targetJoin = self::findJoinByAlias($joinAlias, $joins);
		if (!$targetJoin)
		{
			return null;
		}

		// Build the chain of joins needed to reach the search fields
		$joinChain = self::buildJoinChain($targetJoin, $joins, $parentAlias, $isSnakeCase);
		if (empty($joinChain))
		{
			return null;
		}

		// For aggregated joins (many), include the nested chain that gets aggregated
		// The last item in joinChain is the target. If it's a "many" join,
		// walk its multipleJoin chain to get the actual data joins
		if ($targetJoin->isMultiple())
		{
			$nestedJoin = $targetJoin->getMultipleJoin();
			$usedAliases = [$targetJoin->getAlias() => true];

			while ($nestedJoin)
			{
				// Only add if this is a different alias (to avoid duplicates)
				if (!isset($usedAliases[$nestedJoin->getAlias()]))
				{
					$lastInChain = end($joinChain);
					$joinChain[] = [
						'table' => $nestedJoin->getTableName(),
						'alias' => $nestedJoin->getAlias(),
						'on' => $nestedJoin->getOn(),
						'parentAlias' => $lastInChain['alias'],
						'isSnakeCase' => $isSnakeCase
					];
					$usedAliases[$nestedJoin->getAlias()] = true;
				}
				$nestedJoin = $nestedJoin->getMultipleJoin();
			}
		}

		// Wrap search value with wildcards for LIKE
		$searchPattern = '%' . $searchValue . '%';

		// Build WHERE conditions for search fields
		$conditions = [];
		$params = [];
		foreach ($searchFields as $field)
		{
			$fieldName = $isSnakeCase ? Strings::snakeCase($field) : $field;
			$lastJoin = end($joinChain);
			$conditions[] = "{$lastJoin['alias']}.{$fieldName} LIKE ?";
			$params[] = $searchPattern;
		}

		$whereClause = implode(' OR ', $conditions);

		// Build the complete EXISTS subquery
		$sql = self::buildExistsQuery($joinChain, $whereClause, $parentAlias);

		return [
			'sql' => $sql,
			'params' => $params
		];
	}

	/**
	 * Find a join by its alias.
	 *
	 * @param string $alias
	 * @param array $joins
	 * @return ModelJoin|null
	 */
	protected static function findJoinByAlias(string $alias, array $joins): ?ModelJoin
	{
		foreach ($joins as $join)
		{
			if ($join->getAlias() === $alias)
			{
				return $join;
			}

			// Check nested joins
			$nestedJoin = $join->getMultipleJoin();
			while ($nestedJoin)
			{
				if ($nestedJoin->getAlias() === $alias)
				{
					return $nestedJoin;
				}
				$nestedJoin = $nestedJoin->getMultipleJoin();
			}
		}

		return null;
	}

	/**
	 * Build the chain of joins from parent to target.
	 *
	 * @param ModelJoin $targetJoin
	 * @param array $allJoins
	 * @param string $parentAlias
	 * @param bool $isSnakeCase
	 * @return array
	 */
	protected static function buildJoinChain(ModelJoin $targetJoin, array $allJoins, string $parentAlias, bool $isSnakeCase): array
	{
		$chain = [];

		// Find the path to the target join
		foreach ($allJoins as $join)
		{
			if ($join === $targetJoin)
			{
				// Direct child of parent
				$chain[] = [
					'table' => $join->getTableName(),
					'alias' => $join->getAlias(),
					'on' => $join->getOn(),
					'parentAlias' => $parentAlias,
					'isSnakeCase' => $isSnakeCase
				];
				return $chain;
			}

			// Check if target is nested under this join
			$nestedJoin = $join->getMultipleJoin();
			$nestedChain = [];

			while ($nestedJoin)
			{
				$nestedChain[] = [
					'table' => $nestedJoin->getTableName(),
					'alias' => $nestedJoin->getAlias(),
					'on' => $nestedJoin->getOn(),
					'parentAlias' => empty($nestedChain) ? $join->getAlias() : end($nestedChain)['alias'],
					'isSnakeCase' => $isSnakeCase
				];

				if ($nestedJoin === $targetJoin)
				{
					// Found it! Add the parent join first
					$chain[] = [
						'table' => $join->getTableName(),
						'alias' => $join->getAlias(),
						'on' => $join->getOn(),
						'parentAlias' => $parentAlias,
						'isSnakeCase' => $isSnakeCase
					];
					$chain = array_merge($chain, $nestedChain);
					return $chain;
				}

				$nestedJoin = $nestedJoin->getMultipleJoin();
			}
		}

		return $chain;
	}

	/**
	 * Build the EXISTS query from the join chain.
	 *
	 * @param array $joinChain
	 * @param string $whereClause
	 * @param string $parentAlias
	 * @return string
	 */
	protected static function buildExistsQuery(array $joinChain, string $whereClause, string $parentAlias): string
	{
		$firstJoin = $joinChain[0];
		$parts = [];

		$parts[] = "EXISTS (";
		$parts[] = "  SELECT 1";
		$parts[] = "  FROM {$firstJoin['table']} {$firstJoin['alias']}";

		// Track used aliases to avoid duplicates
		$usedAliases = [$firstJoin['alias'] => true];

		// Add additional joins in the chain
		for ($i = 1; $i < count($joinChain); $i++)
		{
			$join = $joinChain[$i];

			// Skip if this alias was already used
			if (isset($usedAliases[$join['alias']]))
			{
				continue;
			}

			$usedAliases[$join['alias']] = true;
			$parts[] = "  INNER JOIN {$join['table']} {$join['alias']}";
			$parts[] = "    ON " . self::formatOnClause($join['on'], $join['parentAlias'], $join['alias'], $join['isSnakeCase'] ?? true);
		}

		// Main WHERE: connect first join to parent and add search conditions
		$parts[] = "  WHERE " . self::formatOnClause($firstJoin['on'], $firstJoin['parentAlias'], $firstJoin['alias'], $firstJoin['isSnakeCase'] ?? true);

		// Add deleted_at check if applicable
		$parts[] = "  AND {$firstJoin['alias']}.deleted_at IS NULL";

		// Add search conditions
		$parts[] = "  AND ({$whereClause})";
		$parts[] = ")";

		return implode("\n", $parts);
	}

	/**
	 * Format an ON clause for the query.
	 *
	 * @param array $on ON clause [local, foreign]
	 * @param string $leftAlias
	 * @param string $rightAlias
	 * @param bool $isSnakeCase Whether to convert field names to snake_case
	 * @return string
	 */
	protected static function formatOnClause(array $on, string $leftAlias, string $rightAlias, bool $isSnakeCase = true): string
	{
		// Validate ON clause has both fields
		if (!isset($on[0]))
		{
			// Fallback: if ON clause is malformed, return a basic condition
			return "1=1";
		}

		// Check if ON clause is already fully qualified (e.g., 'table.column = othertable.column')
		// or if it's an array with comparison operator (3 elements)
		if (count($on) === 3)
		{
			// Format: [leftField, operator, rightField]
			return "{$on[0]} {$on[1]} {$on[2]}";
		}

		// Standard format: [localField, foreignField] - can be simple or fully qualified
		if (!isset($on[1]))
		{
			return "1=1";
		}

		// Check if first element already contains table alias (contains a dot)
		if (is_string($on[0]) && strpos($on[0], '.') !== false)
		{
			// Extract just the field names from fully qualified format
			// ON format from ModelJoin: [joinTable.field, baseTable.field]
			$leftParts = explode('.', $on[0]);
			$rightParts = explode('.', $on[1]);
			$leftField = end($leftParts);  // field from join table
			$rightField = end($rightParts); // field from base table

			// leftAlias is the base/parent, rightAlias is the join
			return "{$leftAlias}.{$rightField} = {$rightAlias}.{$leftField}";
		}

		// Simple field names - apply snake_case conversion and aliases
		$leftField = $isSnakeCase ? Strings::snakeCase($on[0]) : $on[0];
		$rightField = $isSnakeCase ? Strings::snakeCase($on[1]) : $on[1];
		return "{$leftAlias}.{$leftField} = {$rightAlias}.{$rightField}";
	}
}
