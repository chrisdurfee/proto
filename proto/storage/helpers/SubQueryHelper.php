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
	 * Builds the GROUP_CONCAT SQL snippet.
	 *
	 * @param string $as The alias for the group concat.
	 * @param array $fields The fields to concat.
	 *
	 * @return string|null
	 */
	public static function getGroupConcatSql(string $as, array $fields): ?string
	{
		if (empty($fields))
		{
			return null;
		}

		$keys = array_map(function ($field)
		{
			return "'{$field}-:-', {$field}";
		}, $fields);

		$concat = implode(", '-::-', ", $keys);
		return "GROUP_CONCAT({$concat} SEPARATOR '-:::-') AS {$as}";
	}

	/**
	 * Sets up a subquery for a join using the provided builder callback.
	 *
	 * @param object $join The join object.
	 * @param callable $builderCallback A callback receiving table and alias to return a builder.
	 * @param bool $isSnakeCase Indicates whether to use snake_case.
	 *
	 * @return string|null
	 */
	public static function setupSubQuery(object $join, callable $builderCallback, bool $isSnakeCase = false): ?string
	{
		$tableName = $join->getTableName();
		$alias = $join->getAlias();
		$builder = $builderCallback($tableName, $alias);
		$fields = FieldHelper::formatFields($join->getFields(), $isSnakeCase);

		$joins = [];
		self::addChildJoin($joins, $join, $fields, $isSnakeCase);

		$as = $join->getAs();
		$groupConcat = self::getGroupConcatSql($as, $fields);
		if ($groupConcat === null)
		{
			return null;
		}

		$sql = $builder->select([$groupConcat])->joins($joins);
		return '(' . $sql->where(...$join->getOn()) . ') AS ' . $as;
	}
}
