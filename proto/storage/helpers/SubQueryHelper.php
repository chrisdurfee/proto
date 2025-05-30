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
	 * Generates a subquery join definition for a ModelJoin marked as 'multiple'.
	 * This is used to create a subquery that aggregates related records into JSON.
	 *
	 * @param ModelJoin $multipleJoin The join object representing the multiple relationship.
	 * @param callable $builderCb Callback to get a query builder instance.
	 * @param bool $isSnakeCase Indicates whether to use snake_case for field names.
	 * @return array|null The join definition array or null if invalid.
	 */
	public static function getSubQueryJoinDefinition(
		ModelJoin $multipleJoin,
		callable $builderCb,
		bool $isSnakeCase = false
	): ?array
	{
		$subQuerySql = self::setupSubQuery($multipleJoin, $builderCb, $isSnakeCase);
		if ($subQuerySql === null)
		{
			return null;
		}

		$table = $multipleJoin->getTableName();
		$alias = $multipleJoin->getAlias();

		$on = $multipleJoin->getOn();

		return [
			'table' => "({$subQuerySql})",
			'alias' => $alias,
			'type' => $multipleJoin->getType(),
			'on' => $on,
			'fields' => [
				[ 'json_' . $alias ]
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
			// Use alias.field_name as value, field_name as key
			$key = preg_replace('/^.*\./', '', $field);
			$jsonObjectMap[$key] = $field;
		}

		$currentNestedLink = $aggregationTargetJoin->getMultipleJoin();
		while ($currentNestedLink)
		{
			$nextAggregationTarget = $currentNestedLink->getMultipleJoin();
			if (!$nextAggregationTarget || count($nextAggregationTarget->getFields()) === 0)
			{
				// If the next link isn't an aggregation target, just move to the next link
				$currentNestedLink = $currentNestedLink->getMultipleJoin();
				continue;
			}

			// *** RECURSIVE CALL ***
			$nestedSubQuerySql = self::setupSubQueryForLevel($currentNestedLink, $builderCallback, $isSnakeCase);
			if ($nestedSubQuerySql)
			{
				$nestedAlias = $nextAggregationTarget->getAs() ?? $nextAggregationTarget->getTableName();

				// Remove potential alias prefix if table name was used
				$nestedAlias = preg_replace('/^.*\./', '', $nestedAlias);
				$nestedAlias = $isSnakeCase ? Strings::snakeCase($nestedAlias) : $nestedAlias;

				// Add the raw SQL string as the value for the nested key
				$jsonObjectMap[$nestedAlias] = $nestedSubQuerySql;
			}

			// Move to the join *after* the one we just processed for aggregation
			$currentNestedLink = $nextAggregationTarget->getMultipleJoin();
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
		while ($currentLevelJoin)
		{
			$nextLevelJoinStartsAggregation = false;
			$nestedLink = $currentLevelJoin->getMultipleJoin();
			if ($nestedLink)
			{
				$nestedAggregationTarget = $nestedLink->getMultipleJoin();
				if ($nestedAggregationTarget && count($nestedAggregationTarget->getFields()) > 0)
				{
					$nextLevelJoinStartsAggregation = true;
				}
			}

			// Add the current join to this level's subquery
			$joins[] = [
				'table' => $currentLevelJoin->getTableName(),
				'type' => $currentLevelJoin->getType(),
				'alias' => $currentLevelJoin->getAlias(),
				'on' => $currentLevelJoin->getOn(),
				'using' => $currentLevelJoin->getUsing()
			];

			if ($nextLevelJoinStartsAggregation)
			{
				// Stop adding joins for this level if the next one starts a nested subquery
				break;
			}

			// Move to the next join in the chain for *this* level
			$currentLevelJoin = $currentLevelJoin->getMultipleJoin();
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
				$key = preg_replace('/^.*\./', '', $field);
				$jsonObjectMap[$key] = $field;
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
		$asAlias = preg_replace('/^.*\./', '', $asAlias);
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
}