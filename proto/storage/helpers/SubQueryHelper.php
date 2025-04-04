<?php declare(strict_types=1);
namespace Proto\Storage\Helpers;

use Proto\Storage\Helpers\FieldHelper;

/**
 * Class SubQueryHelper
 *
 * Provides helper methods for building subqueries that utilize
 * a bridge table with a child join and JSON aggregation.
 *
 * The generated subquery uses the parent's join table (via getJoinTableName and getJoinAlias)
 * for the FROM clause and the join's ON clause for the WHERE condition.
 *
 * @package Proto\Storage\Helpers
 */
class SubQueryHelper
{
	/**
	 * Recursively adds child join definitions to the joins array.
	 *
	 * @param array &$joins The joins array to update
	 * @param object $join The join object
	 * @param array &$fields The fields array to merge
	 * @param bool $isSnakeCase Indicates whether to use snake_case
	 *
	 * @return void
	 */
	public static function addChildJoin(array &$joins, object $join, array &$fields, bool $isSnakeCase = false): void
	{
		$childJoin = $join->getMultipleJoin();
		if ($childJoin)
		{
			$alias = $childJoin->getAlias();
			$childFields = FieldHelper::formatFields($childJoin->getFields(), $isSnakeCase, $alias);
			$fields = array_merge($fields, $childFields);

			$joins[] = [
				'table' => $childJoin->getTableName(),
				'type' => $childJoin->getType(),
				'alias' => $alias,
				'on' => $childJoin->getOn(),
				'using' => $childJoin->getUsing()
			];
			self::addChildJoin($joins, $childJoin, $fields, $isSnakeCase);
		}
	}

	/**
	 * Uses the global Json function to generate a JSON array aggregation SQL snippet.
	 *
	 * @param string $as The alias for the JSON array aggregation
	 * @param array $fields The fields to include in the JSON
	 *
	 * @return array|null
	 */
	public static function getJsonAggSql(string $as, array $fields): ?array
	{
		if (empty($fields))
		{
			return null;
		}

		/**
		 * This will remove the table name from the field names.
		 */
		$keys = array_map(function ($field)
		{
			return preg_replace('/^.*\./', '', $field);
		}, $fields);

		return Json($as, array_combine($keys, $fields));
	}

	/**
	 * Retrieves the first ON condition from a join object and converts it
	 * into a simple "column = value" string for the WHERE clause.
	 *
	 * @param object $join The join object
	 *
	 * @return string
	 */
	protected static function getJoinWhere(object $join): string
	{
		$where = $join->getOn();
		if (!is_array($where))
		{
			return $where;
		}
		$values = $where[0];
		$column = $values[0];
		$value = $values[1];
		return "{$column} = {$value}";
	}

	/**
	 * Checks if the join is a bridge join.
	 *
	 * @param object $join The join object
	 * @return bool
	 */
	protected static function isBridge(object $join): bool
	{
		return count($join->getFields()) < 1;
	}

	/**
	 * Sets up a subquery for a join using JSON aggregation.
	 *
	 * When the join is a child join (e.g. for permissions), it uses the parent's
	 * join table name and alias (via getJoinTableName and getJoinAlias) for the FROM clause
	 * and the join's ON clause for the WHERE condition.
	 *
	 * @param object $join The join object (typically the child join)
	 * @param callable $builderCallback A callback that receives (tableName, alias) and returns a query builder
	 * @param bool $isSnakeCase Indicates whether to use snake_case
	 *
	 * @return string|null
	 */
	public static function setupSubQuery(object $join, callable $builderCallback, bool $isSnakeCase = false): ?string
	{
		$tableName = $join->getContextTableName();
		$alias = $join->getContextAlias();
		$as = $join->getAs();

		/**
		 * Check if the join is a bridge join. If it is, we will need to use the parent table name and alias.
		 */
		$isBridge = self::isBridge($join);
		if ($isBridge)
		{
			$tableName = $join->getTableName();
			$alias = $join->getAlias();
		}

		$builder = $builderCallback($tableName, $alias);

		/**
		 * This will get the fields from the join and format them for use in the JSON aggregation.
		 */
		$fields = FieldHelper::formatFields($join->getFields(), $isSnakeCase);

		/**
		 * This will set up the child joins for the join object.
		 */
		$childJoins = [];
		self::addChildJoin($childJoins, $join, $fields, $isSnakeCase);

		/**
		 * This will generate the JSON aggregation SQL snippet using the fields collected
		 * from the join and any child joins. If there are no fields, it returns null.
		 */
		$jsonAggSql = self::getJsonAggSql($as, $fields);
		if ($jsonAggSql === null)
		{
			return null;
		}

		$where = self::getJoinWhere($join);
		$subQuery = $builder->select($jsonAggSql)->joins($childJoins)->where($where);
		return '(' . $subQuery . ') AS ' . $as;
	}
}