<?php declare(strict_types=1);
namespace Proto\Models;

use Proto\Utils\Strings;

/**
 * Class Join
 *
 * Represents a join configuration.
 *
 * @package Proto\Models
 */
class Join
{
	/**
	 * Join settings.
	 *
	 * @var array
	 */
	protected array $settings = [];

	/**
	 * Join constructor.
	 *
	 * @param string $model Model class name.
	 * @param array $settings Join settings.
	 */
	public function __construct(string $model, array $settings)
	{
		$this->setupSettings($model, $settings);
	}

	/**
	 * Convert a string to snake_case.
	 *
	 * @param string $string Input string.
	 * @return string
	 */
	protected function snakeCase(string $string): string
	{
		return Strings::snakeCase($string);
	}

	/**
	 * Prepare a column name.
	 *
	 * @param string $column Column name.
	 * @return string
	 */
	protected function prepareColumn(string $column): string
	{
		return $this->snakeCase($column);
	}

	/**
	 * Get the ON clause string.
	 *
	 * @param string $model Model class name.
	 * @param string $table Table name.
	 * @param mixed $onSettings ON settings.
	 * @param string|null $alias Table alias.
	 * @return string
	 */
	protected function getOnString(string $model, string $table, mixed $onSettings, ?string $alias = null): string
	{
		if (is_string($onSettings))
		{
			return $onSettings;
		}

		$joinModel = $onSettings['model'] ?? null;
		$modelTableName = $alias;
		if (!$modelTableName)
		{
			$modelTableName = ($joinModel) ? $joinModel::table() : $model::table();
		}

		return $modelTableName . '.' . $this->prepareColumn($onSettings['modelField'])
			. ' = ' . $table . '.' . $this->prepareColumn($onSettings['field']);
	}

	/**
	 * Get the join table name.
	 *
	 * @param array $settings Join settings.
	 * @return string
	 */
	protected static function getJoinTable(array $settings): string
	{
		$joinModel = $settings['model'] ?? null;
		return ($joinModel) ? $joinModel::table() : $settings['table'];
	}

	/**
	 * Set up a multiple join.
	 *
	 * @param array $parent Parent join settings.
	 * @param mixed $settings Multiple join settings.
	 * @return mixed
	 */
	protected function setupMultipleJoin(array $parent, mixed $settings): mixed
	{
		if ($settings === true)
		{
			return true;
		}

		$model = $parent['model'] ?? null;
		$join = new static($model, $settings);
		return $join->getSettings();
	}

	/**
	 * Set up join settings.
	 *
	 * @param string $model Model class name.
	 * @param array $settings Join settings.
	 * @return void
	 */
	protected function setupSettings(string $model, array $settings): void
	{
		$table = self::getJoinTable($settings);
		$alias = $settings['alias'] ?? null;
		$customTable = $settings['customTable'] ?? null;
		$on = $this->getOnString($model, $table, $settings['on'], $alias);

		$this->settings = [
			'table' => $table,
			'alias' => $alias,
			'customTable' => $customTable,
			'on' => $on,
			'type' => $settings['type'] ?? 'INNER JOIN',
			'fields' => $settings['fields'] ?? null,
			'multiple' => isset($settings['multiple']) ? $this->setupMultipleJoin($settings, $settings['multiple']) : null
		];
	}

	/**
	 * Get the join settings.
	 *
	 * @return array
	 */
	public function getSettings(): array
	{
		return $this->settings;
	}
}