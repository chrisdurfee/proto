<?php declare(strict_types=1);
namespace Proto\Dispatch\Models\Queue;

use Proto\Models\Model;

/**
 * Queue
 *
 * This will be the base class for all queue models.
 *
 * @package Proto\Dispatch\Models\Queue
 */
abstract class Queue extends Model
{
	/**
	 * This will unserialize the data.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	protected static function unserialize(mixed $data): mixed
	{
		return (gettype($data) === 'string') ? \unserialize($data) : $data;
	}

	/**
	 * This will serialize the data.
	 *
	 * @param mixed $data
	 * @return string
	 */
	protected static function serialize(mixed $data): string
	{
		return (gettype($data) !== 'string') ? \serialize($data) : $data;
	}
}