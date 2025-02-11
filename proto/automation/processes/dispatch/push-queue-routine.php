<?php declare(strict_types=1);
namespace App\Automation\Processes\Dispatch;

use App\Models\Queue\PushQueue;
use App\Dispatch\Dispatch;

/**
 * PushQueueRoutine
 *
 * This will handle the push queue.
 *
 * @package App\Automation\Processes\Dispatch
 */
class PushQueueRoutine extends QueueRoutine
{
    /**
     * This will get the queue model.
     *
     * @param object|null $data
     * @return object
     */
    protected function getModel($data = null)
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

        $result = Dispatch::push($item);
        return ($result->sent === 'yes');
    }
}