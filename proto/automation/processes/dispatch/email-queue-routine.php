<?php declare(strict_types=1);
namespace App\Automation\Processes\Dispatch;

use App\Models\Queue\EmailQueue;
use App\Dispatch\Dispatch;

/**
 * EmailQueueRoutine
 *
 * This will handle the email queue.
 *
 * @package App\Automation\Processes\Dispatch
 */
class EmailQueueRoutine extends QueueRoutine
{
    /**
     * This will get the queue model.
     *
     * @param object|null $data
     * @return object
     */
    protected function getModel($data = null)
    {
        return new EmailQueue($data);
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
        $item->attachments = (is_string($item->attachments))? \unserialize($item->attachments) : null;
        $item->to = $item->recipient;

        $result = Dispatch::email($item);
        return ($result->sent === 'yes');
    }
}