<?php
namespace App\Models\Queue;

use App\Storage\Queue\QueueStorage;

class SmsQueue extends Queue
{
    protected static $tableName = 'sms_queue';

    protected static $fields = [
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
		if(!$data)
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
	 * @return object
	 */
	protected static function augment($data = null)
	{
		if(!$data)
		{
			return $data;
		}

		$attachments = $data->attachments ?? '';
		$data->attachments = gettype($attachments) !== 'string'? \serialize($attachments) : $attachments;
		return $data;
	}

    protected static $storageType = QueueStorage::class;
}