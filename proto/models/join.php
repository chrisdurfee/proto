<?php declare(strict_types=1);
namespace Proto\Models;

use Proto\Utils\Strings;

/**
 * Join
 *
 * This will create a join object.
 *
 * @package Proto\Models
 */
class Join
{
	/**
	 * @var array $settings
	 */
	protected array $settings = [];

	/**
	 * This will set up the settings.
	 *
	 * @param string $model
	 * @param array $settings
	 */
	public function __construct(string $model, array $settings)
	{
		$this->setupSettings($model, $settings);
	}

	/**
	 * This will convert a string to snake case.
	 *
	 * @param string $string
	 * @return string
	 */
	protected function snakeCase(string $string): string
	{
		return Strings::snakeCase($string);
	}

	/**
	 * This will prepare the column.
	 *
	 * @param string $column
	 * @return string
	 */
	protected function prepareColumn(string $column): string
	{
		if (isset($settings['isCamelCase']))
		{
			return $column;
		}

		return $this->snakeCase($column);
	}

	/**
	 * This will get the on string/
	 *
	 * @param string $model
	 * @param string $table
	 * @param mixed $onSettings
	 * @param string|null $alias
	 * @return string
	 */
	protected function getOnString(
		string $model,
		string $table,
		mixed $onSettings,
		?string $alias = null
	): string
	{
		if (gettype($onSettings) === 'string')
		{
			return $onSettings;
		}

		$joinModel = $onSettings['model'] ?? null;
		$modelTableName = $alias;
		if (!$modelTableName)
		{
			/**
			 * @var object $model
			 */
			$modelTableName = ($joinModel)? $joinModel::table() : $model::table();
		}

		return $modelTableName . '.' . $this->prepareColumn($onSettings['modelField']) . ' = ' . $table . '.' . $this->prepareColumn($onSettings['field']);
	}

	/**
	 * This will get the join table name.
	 *
	 * @param array $settings
	 * @return string
	 */
	protected static function getJoinTable(array $settings): string
	{
		$joinModel = $settings['model'] ?? null;
		return ($joinModel)? $joinModel::table() : $settings['table'];
	}

	/**
	 * This will set up the multiple join.
	 *
	 * @param array $parent
	 * @param mixed $settings
	 * @return mixed
	 */
	protected function setupMultipleJoin(array $parent, mixed $settings)
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
	 * This will setup the join settings.
	 *
	 * @param string $model
	 * @param array $settings
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
			'multiple' => (isset($settings['multiple'])? $this->setupMultipleJoin($settings, $settings['multiple']) : null)
		];
	}

	/**
	 * This will get the join settings.
	 *
	 * @return array
	 */
	public function getSettings(): array
	{
		return $this->settings;
	}
}