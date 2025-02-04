<?php declare(strict_types=1);
namespace App\Automation\Processes\Dispatch;

use App\Models\Queue\SmsQueue;
use App\Dispatch\Dispatch;

/**
 * SmsQueueRoutine
 *
 * This will handle the sms queue.
 *
 * @package App\Automation\Processes\Dispatch
 */
class SmsQueueRoutine extends QueueRoutine
{
    /**
     * This will get the queue model.
     *
     * @param object|null $data
     * @return object
     */
    protected function getModel($data = null)
    {
        return new SmsQueue($data);
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
        $item->to = $item->recipient;

        $result = Dispatch::sms($item);
        return ($result->sent === 'yes');
    }
}