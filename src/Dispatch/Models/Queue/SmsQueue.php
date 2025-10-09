<?php declare(strict_types=1);
namespace Proto\Dispatch\Models\Queue;

use Proto\Dispatch\Storage\Queue\QueueStorage;

/**
 * SmsQueue
 *
 * This will handle the sms queue.
 *
 * @package Proto\Dispatch\Models\Queue
 */
class SmsQueue extends Queue
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'sms_queue';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'sq';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'createdAt',
		'updatedAt',
		'agentId',
		'dispatchId',
		'recipient',
		'session',
		'message',
		'attachments',
		'priority',
		'status'
	];

	/**
	 * This can be used to format the data.
	 *
	 * @param object|null $data
	 * @return object|null
	 */
	protected static function format(?object $data): ?object
	{
		if (!$data)
		{
			return $data;
		}

		$data->attachments = static::unserialize($data->attachments ?? '');
		return $data;
	}

	/**
	 * This will allow you to augment the data after
	 * its added to the data mapper.
	 *
	 * @param mixed $data
	 * @return object
	 */
	protected static function augment(mixed $data = null): mixed
	{
		if (!$data)
		{
			return $data;
		}

		$data->attachments = static::serialize($data->attachments ?? '');
		return $data;
	}

	/**
	 * @var string $storageType
	 */
	protected static string $storageType = QueueStorage::class;
}