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
		$joinChain = self::buildJoinChain($targetJoin, $joins, $parentAlias);
		if (empty($joinChain))
		{
			return null;
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
	 * @return array
	 */
	protected static function buildJoinChain(ModelJoin $targetJoin, array $allJoins, string $parentAlias): array
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
					'parentAlias' => $parentAlias
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
					'parentAlias' => empty($nestedChain) ? $join->getAlias() : end($nestedChain)['alias']
				];

				if ($nestedJoin === $targetJoin)
				{
					// Found it! Add the parent join first
					$chain[] = [
						'table' => $join->getTableName(),
						'alias' => $join->getAlias(),
						'on' => $join->getOn(),
						'parentAlias' => $parentAlias
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

		// Add additional joins in the chain
		for ($i = 1; $i < count($joinChain); $i++)
		{
			$join = $joinChain[$i];
			$parts[] = "  INNER JOIN {$join['table']} {$join['alias']}";
			$parts[] = "    ON " . self::formatOnClause($join['on'], $join['parentAlias'], $join['alias']);
		}

		// Main WHERE: connect first join to parent and add search conditions
		$parts[] = "  WHERE " . self::formatOnClause($firstJoin['on'], $firstJoin['parentAlias'], $firstJoin['alias']);

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
	 * @return string
	 */
	protected static function formatOnClause(array $on, string $leftAlias, string $rightAlias): string
	{
		return "{$leftAlias}.{$on[0]} = {$rightAlias}.{$on[1]}";
	}
}
