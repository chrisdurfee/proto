<?php declare(strict_types=1);
namespace Proto\Models;

/**
 * Interface ModelInterface
 *
 * Defines the interface for models.
 *
 * @package Proto\Models
 */
interface ModelInterface
{
	/**
	 * Get the table name for the model.
	 *
	 * @return string|null
	 */
	public function getTableName(): ?string;

	/**
	 * Get the alias for the model.
	 *
	 * @return string|null
	 */
	public function getAlias(): ?string;

	/**
	 * Get the fields of the model.
	 *
	 * @return array
	 */
	public function getFields(): array;
}