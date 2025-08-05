<?php declare(strict_types=1);
namespace Proto\Automation\Processes\Dispatch;

use Proto\Dispatch\Models\Queue\EmailQueue;
use Proto\Dispatch\Dispatcher;
use Proto\Models\Model;

/**
 * EmailQueueRoutine
 *
 * This will handle the email queue.
 *
 * @package Proto\Automation\Processes\Dispatch
 */
class EmailQueueRoutine extends QueueRoutine
{
	/**
	 * This will get the queue model.
	 *
	 * @param object|null $data
	 * @return Model
	 */
	protected function getModel(?object $data = null): Model
	{
		return new EmailQueue($data);
	}

	/**
	 * This will dispatch the item.
	 *
	 * @param object $item
	 * @return bool
	 */
	protected function dispatch(object $item): bool
	{
		$item->compiledTemplate = $item->message;
		$item->attachments = (is_string($item->attachments))? \unserialize($item->attachments) : null;
		$item->to = $item->recipient;

		$result = Dispatcher::email($item);
		return ($result->sent);
	}
}