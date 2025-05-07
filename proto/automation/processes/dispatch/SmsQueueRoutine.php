<?php declare(strict_types=1);
namespace Proto\Automation\Processes\Dispatch;

use Proto\Dispatch\Models\Queue\SmsQueue;
use Proto\Dispatch\Dispatcher;
use Proto\Models\ModelInterface;

/**
 * SmsQueueRoutine
 *
 * This will handle the sms queue.
 *
 * @package Proto\Automation\Processes\Dispatch
 */
class SmsQueueRoutine extends QueueRoutine
{
	/**
	 * This will get the queue model.
	 *
	 * @param object|null $data
	 * @return ModelInterface
	 */
	protected function getModel(?object $data = null): ModelInterface
	{
		return new SmsQueue($data);
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
		$item->to = $item->recipient;

		$result = Dispatcher::sms($item);
		return ($result->sent === 'yes');
	}
}