<?php
namespace App\Models\Queue;

use App\Storage\Queue\QueueStorage;

class PushQueue extends Queue
{
    protected static $tableName = 'web_push_queue';

    protected static $fields = [
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
		if(!$data)
		{
			return $data;
		}

		$data->attachments = self::getAttachments($data);
		$data->subscriptions = gettype($data->subscriptions) === 'string'? \unserialize($data->subscriptions) : $data->subscriptions;
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
		if(isset($attachments))
		{
			if(gettype($attachments) === 'string')
			{
				$attachments = \unserialize($attachments);
				if(empty($attachments))
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
	 * @return object
	 */
	protected static function augment($data = null)
	{
		if(!$data)
		{
			return $data;
		}

		$data->attachments = self::getAttachments($data);
		$data->subscriptions = gettype($data->subscriptions) !== 'string'? \serialize($data->subscriptions) : $data->subscriptions;
		return $data;
	}

    protected static $storageType = QueueStorage::class;
}