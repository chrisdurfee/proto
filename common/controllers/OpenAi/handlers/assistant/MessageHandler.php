<?php declare(strict_types=1);
namespace App\Controllers\OpenAi\Handlers\Assistant;

use App\Controllers\OpenAi\Handlers\Handler;
use function App\Controllers\OpenAi\Handlers\decode;

/**
 * MessageHandler
 *
 * This will handle the assistant thread messages.
 *
 * @package App\Controllers\OpenAi\Handlers\Assistant
 */
class MessageHandler extends Handler
{
    /**
     * This will create a message within a thread.
     *
     * @param string $threadId
     * @param array $data
     * @return object|null
     */
    public function create(
        string $threadId,
        array $data
    ): ?object
    {
        $result = $this->api->createThreadMessage($threadId, $data);
        return decode($result);
    }

    /**
     * This will retrieve the message.
     *
     * @param string $threadId
     * @param string $messageId
     * @return array|null
     */
    public function retrieve(
        string $threadId,
        string $messageId
    ): ?object
    {
        $result = $this->api->retrieveThreadMessage($threadId, $messageId);
        return decode($result);
    }

    /**
     * This will list the assistants.
     *
     * @param string $threadId
     * @return object|null
     */
    public function list(
        string $threadId
    ): ?object
    {
        $query = ['limit' => 10];

        $result = $this->api->listThreadMessages($threadId, $query);
        return decode($result);
    }

    /**
     * This will modify the message.
     *
     * @param string $threadId
     * @param string $messageId
     * @param array $metadata
     * @return object|null
     */
    public function modify(
        string $threadId,
        string $messageId,
        array $metadata = []
    ): ?object
    {
        $data = [
            'metadata' => $metadata,
        ];
        $result = $this->api->modifyThreadMessage($threadId, $messageId, $data);
        return decode($result);
    }
}