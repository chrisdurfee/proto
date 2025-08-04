<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

/**
 * Blueprint
 *
 * This is the base class for all query blueprints.
 *
 * @package Proto\Database\QueryBuilder
 * @abstract
 */
abstract class Blueprint extends Query
{
	/**
	 * Constructs the blueprint.
	 *
	 * @param string $tableName The table name.
	 * @param callable|null $callback Optional callback to configure the blueprint.
	 * @return void
	 */
	public function __construct(string $tableName, ?callable $callback = null)
	{
		parent::__construct($tableName);
		$this->callBack($callback);
	}

	/**
	 * Applies the provided callback to the blueprint.
	 *
	 * @param callable|null $callback The callback to apply.
	 * @return void
	 */
	protected function callBack(?callable $callback = null): void
	{
		if ($callback !== null)
		{
			call_user_func($callback, $this);
		}
	}
}
