<?php declare(strict_types=1);
namespace Proto\Dispatch\Models\Queue;

use Proto\Dispatch\Storage\Queue\EmailQueueStorage;

/**
 * EmailQueue
 *
 * This will handle the email queue.
 *
 * @package Proto\Dispatch\Models\Queue
 */
class EmailQueue extends Queue
{
	/**
	 * @var string $tableName
	 */
	protected static string $tableName = 'email_queue';

	/**
	 * @var string $alias
	 */
	protected static string $alias = 'eq';

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
		'from',
		'fromName',
		'subject',
		'message',
		'unsubscribeUrl',
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

		$attachments = $data->attachments ?? '';
		$data->attachments = gettype($attachments) === 'string'? \unserialize($attachments) : $attachments;
		return $data;
	}

	/**
	 * This will allow you to augment the data after
	 * its added to the data mapper.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	protected static function augment($data = null): mixed
	{
		if (!$data)
		{
			return $data;
		}

		$attachments = $data->attachments ?? '';
		$data->attachments = gettype($attachments) !== 'string'? \serialize($attachments) : $attachments;
		return $data;
	}

	/**
	 * @var string $storageType
	 */
	protected static string $storageType = EmailQueueStorage::class;
}