<?php declare(strict_types=1);
namespace Proto\Dispatch\Models\Queue;

use Proto\Dispatch\Storage\Queue\QueueStorage;

/**
 * PushQueue
 *
 * This will handle the push queue.
 *
 * @package Proto\Dispatch\Models\Queue
 */
class PushQueue extends Queue
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'web_push_queue';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'wq';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'createdAt',
		'updatedAt',
		'agentId',
		'dispatchId',
		'subscriptions',
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

		$data->attachments = self::getAttachments($data);
		$data->subscriptions = static::unserialize($data->subscriptions ?? '');
		return $data;
	}

	/**
	 * This will get the attachments
	 *
	 * @param string|object $attachments
	 * @return object|null
	 */
	protected static function getAttachments($data): ?object
	{
		$attachments = $data->attachments ?? null;
		if (isset($attachments))
		{
			if (gettype($attachments) === 'string')
			{
				$attachments = \unserialize($attachments);
				if (empty($attachments))
				{
					$attachments = null;
				}
			}
		}

		return $attachments;
	}

	/**
	 * This will allow you to augment the data after
	 * its added to the data mapper.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	protected static function augment(mixed $data = null): mixed
	{
		if (!$data)
		{
			return $data;
		}

		$data->attachments = self::getAttachments($data);
		$data->subscriptions = static::serialize($data->subscriptions ?? '');
		return $data;
	}

	/**
	 * @var string $storageType
	 */
	protected static string $storageType = QueueStorage::class;
}