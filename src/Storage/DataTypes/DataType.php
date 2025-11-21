<?php declare(strict_types=1);
namespace Proto\Storage\DataTypes;

/**
 * DataType
 *
 * Base class for custom data type handlers.
 * Allows models to define how complex types should be stored and retrieved.
 *
 * @package Proto\Storage\DataTypes
 */
abstract class DataType
{
	/**
	 * Get the placeholder string for this data type in prepared statements.
	 *
	 * @return string
	 */
	abstract public function getPlaceholder(): string;

	/**
	 * Convert the model value to parameters for binding.
	 *
	 * @param mixed $value The raw value from the model
	 * @return array Array of parameter values to bind
	 */
	abstract public function toParams(mixed $value): array;

	/**
	 * Get the column assignment for UPDATE statements.
	 *
	 * @param string $column The column name
	 * @return string The SET clause fragment (e.g., "`col` = POINT(?, ?)")
	 */
	public function getUpdateClause(string $column): string
	{
		return "`{$column}` = " . $this->getPlaceholder();
	}

	/**
	 * Determine if this value should use the custom type handler.
	 *
	 * @param mixed $value
	 * @return bool
	 */
	public function shouldHandle(mixed $value): bool
	{
		return $value !== null;
	}
}
