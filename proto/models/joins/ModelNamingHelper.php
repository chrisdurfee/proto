<?php declare(strict_types=1);
namespace Proto\Models\Joins;

use Proto\Utils\Strings;

/**
 * ModelNamingHelper
 *
 * Handles deriving conventional names (table, alias, keys) from model classes.
 *
 * @package Proto\Models\Joins
 */
class ModelNamingHelper
{
	/**
	 * ModelNamingConvention constructor.
	 *
	 * @param bool $isSnakeCase
	 */
	public function __construct(
		protected bool $isSnakeCase = true
	)
	{
	}

	/**
	 * Gets the base table name from the model class.
	 * Assumes the model has a static table() method.
	 *
	 * @param string $modelClassName
	 * @return string|array Returns table name or array [db, table]
	 */
	public function getTableName(string $modelClassName): string|array
	{
		return $modelClassName::table();
	}

	/**
	 * Gets the default table alias from the model class.
	 * Assumes the model has a static alias() method.
	 *
	 * @param string $modelClassName
	 * @return string|null
	 */
	public function getTableAlias(string $modelClassName): ?string
	{
		return $modelClassName::alias();
	}

	/**
	 * Gets the base name used for foreign key generation.
	 * Assumes the model has a static getIdClassName() method.
	 *
	 * @param string $modelClassName
	 * @return string
	 */
	protected function getBaseIdName(string $modelClassName): string
	{
		return $modelClassName::getIdClassName();
	}

	/**
	 * Gets the conventional reference name (e.g., 'user_profile').
	 *
	 * @param string $modelClassName
	 * @return string
	 */
	public function getReferenceName(string $modelClassName): string
	{
		$baseName = $this->getBaseIdName($modelClassName);
		return $this->isSnakeCase ? Strings::snakeCase($baseName) : $baseName;
	}

	/**
	 * Gets the conventional foreign key column name (e.g., 'userProfileId' or 'user_profile_id').
	 *
	 * @param string $modelClassName
	 * @return string
	 */
	public function getForeignKeyName(string $modelClassName): string
	{
		$refName = $this->getReferenceName($modelClassName);
		return ($this->isSnakeCase) ? "{$refName}_id" : "{$refName}Id";
	}
}