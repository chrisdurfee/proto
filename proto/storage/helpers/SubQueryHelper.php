<?php declare(strict_types=1);
namespace Proto\Storage\Helpers;

use Proto\Storage\Helpers\FieldHelper;

/**
 * Class SubQueryHelper
 *
 * Provides helper methods for building subqueries that utilize
 * a "bridge table → final table" join with JSON aggregation.
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
			$childFields = FieldHelper::formatFields($childJoin->getFields(), $isSnakeCase);
			$fields = array_merge($fields, $childFields);
			$joins[] = [
				'table' => $childJoin->getTableName(),
				'type' => $childJoin->getType(),
				'alias' => $childJoin->getAlias(),
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
		return Json($as, array_combine($fields, $fields));
	}

	/**
	 * Retrieves the first ON condition from the join object and converts it
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
	 * Sets up a subquery for a join using JSON aggregation.
	 *
	 * Example output:
	 *   (
	 *     SELECT JSON_ARRAYAGG(
	 *       JSON_OBJECT('id', p.id, 'name', p.name, ...)
	 *     )
	 *     FROM role_permissions rp
	 *     LEFT JOIN permissions p ON rp.permission_id = p.id
	 *     WHERE rp.role_id = r.id
	 *   ) AS permissions
	 *
	 * @param object $join The join object
	 * @param callable $builderCallback A callback receiving (tableName, alias) → returns a builder
	 * @param bool $isSnakeCase Indicates whether to use snake_case
	 *
	 * @return string|null
	 */
	public static function setupSubQuery(object $join, callable $builderCallback, bool $isSnakeCase = false): ?string
	{
		echo '<pre>';
		var_dump($join);

		$tableName = $join->getTableName();
		$alias = $join->getAlias();
		$as = $join->getAs();
		$builder = $builderCallback($tableName, $alias);

		$fields = FieldHelper::formatFields($join->getFields(), $isSnakeCase);

		$joins = [];
		self::addChildJoin($joins, $join, $fields, $isSnakeCase);

		$jsonAggSql = self::getJsonAggSql($as, $fields);
		if ($jsonAggSql === null)
		{
			return null;
		}

		$where = self::getJoinWhere($join);
		$subQuery = $builder->select($jsonAggSql)->joins($joins)->where($where);
		return '(' . $subQuery . ') AS ' . $as;
	}
}