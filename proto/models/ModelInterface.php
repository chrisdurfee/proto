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
	 * Get the data for the model.
	 *
	 * @return object
	 */
	public function getData(): object;
}