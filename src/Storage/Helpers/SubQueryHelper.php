<?php declare(strict_types=1);
namespace Proto\Storage\Helpers;

use Proto\Models\Joins\ModelJoin;
use Proto\Database\QueryBuilder\QueryHandler;
use Proto\Utils\Strings;

/**
 * Class SubQueryHelper
 *
 * Provides helper methods for building potentially nested subqueries
 * using JSON aggregation for one-to-many or many-to-many relationships.
 *
 * @package Proto\Storage\Helpers
 */
class SubQueryHelper
{
	/**
	 * Build a join‐able subquery definition for a ModelJoin marked multiple.
	 *
	 * @param ModelJoin $join The “multiple” join.
	 * @param callable $builderCb fn($table, $alias): QueryHandler.
	 * @param bool $isSnakeCase
	 * @return array|null
	 */
	public static function getSubQueryJoinDefinition(
		ModelJoin $join,
		callable $builderCb,
		bool $isSnakeCase = false
	): ?array
	{
		$aggTarget = $join->getMultipleJoin();
		if (! $aggTarget)
		{
			return null;
		}

		$jsonMap = self::buildJsonObjectStructure(
			$aggTarget,
			$builderCb,
			$isSnakeCase
		);

		$jsonColumn = $aggTarget->getAs() ?? $aggTarget->getTableName();
		if ($isSnakeCase)
		{
			$jsonColumn = Strings::snakeCase($jsonColumn);
		}

		[$jsonSql] = self::getJsonAggSql($jsonColumn, $jsonMap);

		/**
		 * The on clause needs to be added to the subquery
		 * to ensure the join works correctly.
		 */
		$onClause = $join->getOn()[0];
		$keyColumn = $onClause[count($onClause) - 1];

		$innerJoins = [];
		self::collectJoinsForLevel($join, $innerJoins);

		$subQuery = $builderCb(
			$join->getTableName(),
			$join->getAlias()
		)
			->select(
				[ $keyColumn ],
				[ $jsonSql, $jsonColumn ]
			)
			->joins($innerJoins)
			->groupBy($keyColumn);

		return [
			'table' => "({$subQuery})",
			'alias' => $join->getAlias(),
			'type' => $join->getType(),
			'on' => $join->getOn(),
			'fields' => [
				[ $keyColumn ],
				[ "{$jsonColumn}", $jsonColumn ]
			]
		];
	}

	/**
	 * Builds the SELECT part (JSON aggregation) for a given level of nesting.
	 * Recursively calls setupSubQueryForLevel for nested aggregations.
	 *
	 * @param ModelJoin $aggregationTargetJoin The join representing the collection to aggregate (e.g., Role, Permission).
	 * @param callable $builderCallback Callback to get a query builder instance.
	 * @param bool $isSnakeCase Snake case flag.
	 * @return array Key-value pairs for the JSON_OBJECT, where values can be field names or nested SQL subqueries.
	 */
	private static function buildJsonObjectStructure(ModelJoin $aggregationTargetJoin, callable $builderCallback, bool $isSnakeCase): array
	{
		$jsonObjectMap = [];
		$alias = $aggregationTargetJoin->getAlias();
		$directFields = FieldHelper::formatFields($aggregationTargetJoin->getFields(), $isSnakeCase, $alias);
		foreach ($directFields as $field)
		{
			// Handle array fields [fieldName, alias] or [[raw_sql], alias]
			if (is_array($field))
			{
				// For array fields, use the alias as the key and the field/raw SQL as the value
				if (count($field) >= 2)
				{
					$key = $field[1]; // The alias is already formatted (snake_case if needed)
					$value = is_array($field[0]) ? $field[0][0] : $field[0]; // Get raw SQL or field name
					$jsonObjectMap[$key] = $value;
				}
			}
			else
			{
				// Simple string field: use field_name as key, alias.field_name as value
				$key = self::removeTablePrefix($field);
				$jsonObjectMap[$key] = $field;
			}
		}

		$currentNestedLink = $aggregationTargetJoin->getMultipleJoin();
		while ($currentNestedLink)
		{
			// Check if this is a 'multiple' join (another aggregation level)
			if ($currentNestedLink->isMultiple())
			{
				// This is a nested .many() - handle it as a recursive aggregation
				$nextAggregationTarget = $currentNestedLink->getMultipleJoin();
				if ($nextAggregationTarget && count($nextAggregationTarget->getFields()) > 0)
				{
					// *** RECURSIVE CALL for nested .many() ***
					$nestedSubQuerySql = self::setupSubQueryForLevel($currentNestedLink, $builderCallback, $isSnakeCase);
					if ($nestedSubQuerySql)
					{
						$nestedAlias = $nextAggregationTarget->getAs() ?? $nextAggregationTarget->getTableName();

						// Remove potential alias prefix if table name was used
						$nestedAlias = self::removeTablePrefix($nestedAlias);
						$nestedAlias = $isSnakeCase ? Strings::snakeCase($nestedAlias) : $nestedAlias;

						// Add the raw SQL string as the value for the nested key
						$jsonObjectMap[$nestedAlias] = $nestedSubQuerySql;
					}

					// Move to the join *after* the one we just processed for aggregation
					$currentNestedLink = $nextAggregationTarget->getMultipleJoin();
					continue;
				}
			}
			else
			{
				// This is a .one() join - add its fields directly to this level
				if (count($currentNestedLink->getFields()) > 0)
				{
					$nestedAlias = $currentNestedLink->getAlias();
					$nestedFields = FieldHelper::formatFields($currentNestedLink->getFields(), $isSnakeCase, $nestedAlias);

					foreach ($nestedFields as $field)
					{
						// Handle array fields [fieldName, alias] or [[raw_sql], alias]
						if (is_array($field))
						{
							if (count($field) >= 2)
							{
								$key = $field[1];
								$value = is_array($field[0]) ? $field[0][0] : $field[0];
								$jsonObjectMap[$key] = $value;
							}
						}
						else
						{
							$key = self::removeTablePrefix($field);
							$jsonObjectMap[$key] = $field;
						}
					}
				}
			}

			// Move to the next link in the chain
			$currentNestedLink = $currentNestedLink->getMultipleJoin();
		}

		return $jsonObjectMap;
	}

	/**
	 * Collects JOIN clauses needed *within* a specific subquery level.
	 * Stops when it hits a join that starts a nested aggregation.
	 *
	 * @param ModelJoin $startJoin The join linking *into* the current aggregation level (e.g., UserRole for Role aggregation).
	 * @param array &$joins Array to populate with join definitions.
	 */
	private static function collectJoinsForLevel(ModelJoin $startJoin, array &$joins): void
	{
		$currentLevelJoin = $startJoin->getMultipleJoin();

		if (!$currentLevelJoin)
		{
			// No multipleJoin to process
			return;
		}

		// For many-to-many relationships, the startJoin points to the bridge table (FROM clause),
		// and the first multipleJoin points to the related table (needs JOIN).
		// For one-to-many created by JoinBuilder->many(), both point to the same table (duplicate).
		//
		// We skip the join ONLY if it's BOTH a duplicate AND the final aggregation target.
		$fromTable = $startJoin->getTableName();
		$fromAlias = $startJoin->getAlias();

		$isFirstJoin = true;

		while ($currentLevelJoin)
		{
			$nextLevelJoinStartsAggregation = false;
			$nestedLink = $currentLevelJoin->getMultipleJoin();

			// Check if this join is a duplicate of the FROM table (one-to-many case)
			$isDuplicateOfFrom = $isFirstJoin
				&& $currentLevelJoin->getTableName() === $fromTable
				&& $currentLevelJoin->getAlias() === $fromAlias;

			// Check if this join is marked as 'multiple' - if so, it starts a nested aggregation
			if ($currentLevelJoin->isMultiple())
			{
				$nestedAggregationTarget = $currentLevelJoin->getMultipleJoin();
				if ($nestedAggregationTarget && count($nestedAggregationTarget->getFields()) > 0)
				{
					$nextLevelJoinStartsAggregation = true;
				}
			}

			// For one-to-many (simple ->many()), the first join in the chain is a duplicate
			// of the FROM table and should be skipped. It's recognizable because:
			// - It's the first join in the chain
			// - It has the same table/alias as the FROM clause
			// - It's NOT marked as multiple (it's the aggregation target, not another bridge)
			$shouldSkip = $isDuplicateOfFrom && !$currentLevelJoin->isMultiple();

			if (!$shouldSkip)
			{
				$joins[] = [
					'table' => $currentLevelJoin->getTableName(),
					'type' => $currentLevelJoin->getType(),
					'alias' => $currentLevelJoin->getAlias(),
					'on' => $currentLevelJoin->getOn(),
					'using' => $currentLevelJoin->getUsing()
				];
			}

			// Stop if we've reached the end of this level's chain (nested aggregation starts)
			if ($nextLevelJoinStartsAggregation)
			{
				break;
			}

			// Move to the next join in the chain
			$currentLevelJoin = $currentLevelJoin->getMultipleJoin();
			$isFirstJoin = false;
		}
	}

	/**
	 * Sets up the bridge fields for the JSON_OBJECT.
	 * This is a helper function to add fields from the bridge join to the JSON_OBJECT.
	 *
	 * @param ModelJoin $bridgeJoin The join representing the bridge table (e.g., UserRole, RolePermission).
	 * @param array &$jsonObjectMap The JSON_OBJECT map to populate with field mappings.
	 * @param bool $isSnakeCase Snake case flag.
	 */
	protected static function setupBridgeFields(ModelJoin $bridgeJoin, array &$jsonObjectMap, bool $isSnakeCase): void
	{
		if (count($bridgeJoin->getFields()) > 0)
		{
			// If the bridge join has fields, add them to the JSON_OBJECT
			$bridgeFields = FieldHelper::formatFields($bridgeJoin->getFields(), $isSnakeCase, $bridgeJoin->getAlias());
			foreach ($bridgeFields as $field)
			{
				// Handle array fields [fieldName, alias] or [[raw_sql], alias]
				if (is_array($field))
				{
					// For array fields, use the alias as the key and the field/raw SQL as the value
					if (count($field) >= 2)
					{
						$key = $field[1]; // The alias is already formatted (snake_case if needed)
						$value = is_array($field[0]) ? $field[0][0] : $field[0]; // Get raw SQL or field name
						$jsonObjectMap[$key] = $value;
					}
				}
				else
				{
					// Simple string field
					$key = self::removeTablePrefix($field);
					$jsonObjectMap[$key] = $field;
				}
			}
		}
	}

	/**
	 * Generates the complete subquery SQL string for a specific aggregation level.
	 * This is the function called recursively.
	 *
	 * @param ModelJoin $bridgeJoin The join representing the bridge table *into* this level (e.g., UserRole, RolePermission).
	 * @param callable $builderCallback Callback to get a query builder instance.
	 * @param bool $isSnakeCase Snake case flag.
	 * @return string|null The generated SQL subquery string, or null if invalid.
	 */
	private static function setupSubQueryForLevel(ModelJoin $bridgeJoin, callable $builderCallback, bool $isSnakeCase): ?string
	{
		$aggregationTargetJoin = $bridgeJoin->getMultipleJoin();
		if (!$aggregationTargetJoin)
		{
			// Cannot aggregate if there's no target join after the bridge
			return null;
		}

		// 1. Build the JSON_OBJECT structure (handles recursion for nested parts)
		$jsonObjectMap = self::buildJsonObjectStructure($aggregationTargetJoin, $builderCallback, $isSnakeCase);
		if (empty($jsonObjectMap))
		{
			return null;
		}

		self::setupBridgeFields($bridgeJoin, $jsonObjectMap, $isSnakeCase);

		// 2. Generate the JSON_ARRAYAGG(JSON_OBJECT(...)) SQL part
		$jsonAggSelect = self::getJsonAggSql('dummy_alias', $jsonObjectMap); // Alias doesn't matter here
		if (!$jsonAggSelect)
		{
			return null;
		}

		// Extract the core JSON_ARRAYAGG part
		$selectSql = $jsonAggSelect[0][0]; // Assumes Json() returns [[sql], alias]

		// 3. Determine FROM table and alias (uses the bridge table)
		$fromTable = $bridgeJoin->getTableName();
		$fromAlias = $bridgeJoin->getAlias();

		// 4. Determine the WHERE clause (connects bridge table to the *outer* query level)
		$whereClause = self::getJoinWhere($bridgeJoin);
		if (empty($whereClause))
		{
			return null;
		}

		// 5. Collect JOIN clauses needed *within* this subquery level
		$joinsForThisLevel = [];
		self::collectJoinsForLevel($bridgeJoin, $joinsForThisLevel);

		// 6. Build the subquery SQL string
		/** @var QueryHandler $builder */
		$builder = $builderCallback($fromTable, $fromAlias);
		return (string)($builder
			->select([$selectSql]) // Pass the raw JSON_ARRAYAGG string
			->joins($joinsForThisLevel)
			->where($whereClause));
	}

	/**
	 * Main entry point called from Storage.
	 * Sets up the *outermost* subquery for a join marked as 'multiple'.
	 *
	 * @param ModelJoin $initialMultipleJoin The first join marked as multiple (e.g., UserRole join from User).
	 * @param callable $builderCallback A callback that receives (tableName, alias) and returns a query builder.
	 * @param bool $isSnakeCase Indicates whether to use snake_case.
	 * @return string|null The generated SQL subquery string including the outer alias, e.g., "(SELECT ...) AS roles", or null.
	 */
	public static function setupSubQuery(ModelJoin $initialMultipleJoin, callable $builderCallback, bool $isSnakeCase = false): ?string
	{
		// Generate the SQL for the first level aggregation
		$subQuerySql = self::setupSubQueryForLevel($initialMultipleJoin, $builderCallback, $isSnakeCase);
		if (!$subQuerySql)
		{
			return null;
		}
		// Determine the final alias for this subquery in the main query's SELECT list
		$aggregationTargetJoin = $initialMultipleJoin->getMultipleJoin();
		$asAlias = $aggregationTargetJoin->getAs() ?? $aggregationTargetJoin->getTableName();
		$asAlias = self::removeTablePrefix($asAlias);
		$asAlias = $isSnakeCase ? Strings::snakeCase($asAlias) : $asAlias;

		// Return the subquery wrapped in parentheses with its final alias
		return '(' . $subQuerySql . ') AS ' . $asAlias;
	}

	/**
	 * Helper to generate JSON aggregation SQL.
	 * IMPORTANT: Needs to handle raw SQL strings provided in the $fieldValueMap
	 * by wrapping them in parentheses for use as scalar subqueries.
	 *
	 * @param string $as The alias for the resulting JSON array.
	 * @param array $fieldValueMap Map of JSON key => SQL field name OR raw SQL subquery string (assumed to start with SELECT).
	 * @return array|null [[json_agg_sql_string], desired_alias]
	 */
	public static function getJsonAggSql(string $as, array $fieldValueMap): ?array
	{
		if (empty($fieldValueMap))
		{
			// Return a valid JSON empty array representation for the specific database
			return [[ 'COALESCE(JSON_ARRAY(), CAST("[]" AS JSON))' ], $as];
		}

		$jsonObjectsParts = [];
		foreach ($fieldValueMap as $key => $value)
		{
			// Improved check: Case-insensitive and trims whitespace before checking prefix
			$isSubquery = is_string($value) && str_starts_with(strtoupper(trim($value)), 'SELECT');

			// Escape the key for safety within the SQL string literal
			$escapedKey = addslashes($key);
			if ($isSubquery)
			{
				// It's a raw SQL subquery string - *** WRAP in parentheses ***
				$jsonObjectsParts[] = "'" . $escapedKey . "', (" . $value . ")";
			}
			else
			{
				// It's a regular field name or other literal
				$jsonObjectsParts[] = "'" . $escapedKey . "', " . $value;
			}
		}

		$sql = 'JSON_ARRAYAGG(JSON_OBJECT(' . implode(', ', $jsonObjectsParts) . '))';
		return [[$sql], $as];
	}

	/**
	 * Extracts the primary ON condition for linking the subquery.
	 * Assumes the first ON condition is the relevant one linking parent -> bridge.
	 *
	 * @param ModelJoin $join The *bridge* join object.
	 * @return string
	 */
	protected static function getJoinWhere(ModelJoin $join): string
	{
		$onConditions = $join->getOn();
		if (empty($onConditions) || !is_array($onConditions[0]) || count($onConditions[0]) < 2)
		{
			return '';
		}

		// Assumes the ON condition is [outer_table.key, bridge_table.key] or [..., operator, ...]
		$conditionParts = $onConditions[0];
		$left = $conditionParts[0];
		$right = end($conditionParts);
		$operator = count($conditionParts) === 3 ? $conditionParts[1] : '=';

		// Construct the condition suitable for the WHERE clause of the subquery
		return "{$left} {$operator} {$right}";
	}

	/**
	 * Removes the table prefix from a field or alias (everything before the last dot).
	 *
	 * @param string $fieldName The field name or alias that may contain a table prefix.
	 * @return string The field name without the table prefix.
	 */
	protected static function removeTablePrefix(string $fieldName): string
	{
		return preg_replace('/^.*\./', '', $fieldName);
	}
}