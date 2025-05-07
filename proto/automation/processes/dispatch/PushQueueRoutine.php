<?php declare(strict_types=1);
namespace Proto\Automation\Processes\Dispatch;

use Proto\Dispatch\Models\Queue\PushQueue;
use Proto\Dispatch\Dispatcher;
use Proto\Models\ModelInterface;

/**
 * PushQueueRoutine
 *
 * This will handle the push queue.
 *
 * @package Proto\Automation\Processes\Dispatch
 */
class PushQueueRoutine extends QueueRoutine
{
	/**
	 * This will get the queue model.
	 *
	 * @param object|null $data
	 * @return ModelInterface
	 */
	protected function getModel(?object $data = null): ModelInterface
	{
		return new PushQueue($data);
	}

	/**
	 * This will dispatch the item.
	 *
	 * @param object $item
	 * @return bool
	 */
	protected function dispatch($item): bool
	{
		$item->compiledTemplate = $item->message;
		$item->subscriptions = \unserialize($item->subscriptions);

		$result = Dispatcher::push($item);
		return ($result->sent === 'yes');
	}
}