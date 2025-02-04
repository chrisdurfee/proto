<?php declare(strict_types=1);
namespace Proto\Models;

/**
 * JoinMapper
 *
 * This will map the joins.
 *
 * @package Proto\Models
 */
class JoinMapper
{
	/**
	 * This will convert the model joins.
	 *
     * @param string $model
     * @param array $joins
	 * @return array|null
	 */
	public static function mapJoins(
		string $model,
		string $tableName,
		?string $alias,
		array $joins
	): ?array
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
     * This will map a join.
     *
     * @param string $model
     * @param array $join
     * @param JoinBuilder $builder
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
	 * This will map the join settings.
	 *
	 * @param array $settings
	 * @return array
	 */
	protected static function getJoinSettings(array $settings): array
	{
		return [
			'type' => $settings['type'] ?? null,
			'alias' => $settings['alias'] ?? $settings['as'] ?? null,
			'table' => isset($settings['customTable'])? [$settings['customTable']] : $settings['table'],
			'on' => $settings['on'] ?? null,
			'as' => $settings['as'] ?? null,
			'multiple' => $settings['multiple'] ?? null,
			'fields' => $settings['fields'] ?? []
		];
	}

	/**
	 * This will setup a join.
	 *
     * @param string $model
	 * @param array $settings
	 * @return array
	 */
	protected static function setupJoin(string $model, ?array $settings = null): array
	{
		$join = new Join($model, $settings);
		return self::getJoinSettings($join->getSettings());
	}
}