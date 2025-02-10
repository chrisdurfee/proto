<?php declare(strict_types=1);
namespace Proto\Models;

/**
 * Class JoinMapper
 *
 * Maps model join configurations to join objects.
 *
 * @package Proto\Models
 */
class JoinMapper
{
	/**
	 * Map joins for a given model.
	 *
	 * @param string $model Model class name.
	 * @param string $tableName Table name.
	 * @param string|null $alias Table alias.
	 * @param array $joins Joins array.
	 * @return array|null
	 */
	public static function mapJoins(string $model, string $tableName, ?string $alias, array $joins): ?array
	{
		$mapped = [];
		$builder = new JoinBuilder($mapped, $tableName, $alias);
		foreach ($joins as $join)
		{
			self::map($model, $join, $builder);
		}
		return $mapped;
	}

	/**
	 * Map a single join.
	 *
	 * @param string $model Model class name.
	 * @param array $join Join settings.
	 * @param JoinBuilder $builder Join builder instance.
	 * @return void
	 */
	protected static function map(string $model, array $join, &$builder): void
	{
		$join = self::setupJoin($model, $join);
		$modelJoin = $builder->join($join['table'], $join['alias'])
			->addType($join['type'])
			->on($join['on'])
			->fields(...$join['fields']);

		if (!empty($join['as']))
		{
			$modelJoin->as($join['as']);
		}

		if (empty($join['multiple']))
		{
			return;
		}

		if ($join['multiple'] === true)
		{
			$modelJoin->multiple();
			return;
		}

		$multiple = self::setupJoin($model, $join['multiple']);
		$table = $multiple['table'] ?? null;
		$alias = $multiple['alias'] ?? null;
		$modelJoin->multiple($table, $alias)
			->addType($multiple['type'])
			->on($multiple['on'])
			->fields(...$multiple['fields']);
	}

	/**
	 * Get join settings.
	 *
	 * @param array $settings Join settings.
	 * @return array
	 */
	protected static function getJoinSettings(array $settings): array
	{
		return [
			'type' => $settings['type'] ?? null,
			'alias' => $settings['alias'] ?? $settings['as'] ?? null,
			'table' => isset($settings['customTable']) ? [$settings['customTable']] : $settings['table'],
			'on' => $settings['on'] ?? null,
			'as' => $settings['as'] ?? null,
			'multiple' => $settings['multiple'] ?? null,
			'fields' => $settings['fields'] ?? []
		];
	}

	/**
	 * Set up a join configuration.
	 *
	 * @param string $model Model class name.
	 * @param array|null $settings Join settings.
	 * @return array
	 */
	protected static function setupJoin(string $model, ?array $settings = null): array
	{
		$join = new Join($model, $settings);
		return self::getJoinSettings($join->getSettings());
	}
}