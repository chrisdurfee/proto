<?php declare(strict_types=1);
namespace Proto\Storage\Helpers;

use Proto\Storage\Helpers\FieldHelper;

/**
 * Class SubQueryHelper
 *
 * Provides helper methods for building subqueries.
 *
 * @package Proto\Storage\Helpers
 */
class SubQueryHelper
{
	/**
	 * Recursively adds child join definitions to the joins array.
	 *
	 * @param array &$joins The joins array to update.
	 * @param object $join The join object.
	 * @param array &$fields The fields array to merge.
	 * @param bool $isSnakeCase Indicates whether to use snake_case.
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
	 * @param string $as The alias for the JSON array aggregation.
	 * @param array $fields The fields to include in the JSON.
	 *
	 * @return array|null
	 */
	public static function getJsonAggSql(string $as, array $fields): ?array
	{
		if (empty($fields))
		{
			return null;
		}

		// Use the global Json() function to generate the JSON aggregation
		return Json($as, array_combine($fields, $fields));
	}

	/**
	 * Sets up a subquery for a join using JSON aggregation.
	 *
	 * @param object $join The join object.
	 * @param callable $builderCallback A callback receiving table and alias to return a builder.
	 * @param bool $isSnakeCase Indicates whether to use snake_case.
	 *
	 * @return array|null
	 */
	public static function setupSubQuery(object $join, callable $builderCallback, bool $isSnakeCase = false): ?array
	{
		$tableName = $join->getTableName();
		$alias = $join->getAlias();
		$builder = $builderCallback($tableName, $alias);
		$fields = FieldHelper::formatFields($join->getFields(), $isSnakeCase);

		$joins = [];
		self::addChildJoin($joins, $join, $fields, $isSnakeCase);

		$as = $join->getAs();
		$jsonAggSql = self::getJsonAggSql($as, $fields);
		if ($jsonAggSql === null)
		{
			return null;
		}

		// Generate the subquery for JSON aggregation using the global Json() function
		return $builder->select([$jsonAggSql])->joins($joins);
	}
}
