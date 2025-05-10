<?php declare(strict_types=1);
namespace App\Controllers\OpenAi\Handlers\Assistant;

use App\Controllers\OpenAi\Handlers\Handler;
use function App\Controllers\OpenAi\Handlers\decode;

/**
 * ThreadHandler
 *
 * This will handle the assistant threads.
 *
 * @package App\Controllers\OpenAi\Handlers\Assistant
 */
class ThreadHandler extends Handler
{
    /**
     * This will create a thread that assistants can interact with.
     *
     * @param array $messages
     * @return object|null
     */
    public function create(
        array $messages
    ): ?object
    {
        $data = [
            'messages' => [
                $messages,
            ],
        ];

        $result = $this->api->createThread($data);
        return decode($result);
    }

    /**
     * This will create a thread and runs it in one request.
     *
     * @param string $assistantId
     * @param array $messages
     * @return object|null
     */
    public function createAndRun(
        string $assistantId,
        array $messages
    ): ?object
    {
        $data = [
            'assistant_id' => $assistantId,
            'thread' => [
                'messages' => [
                    $messages
                ],
            ],
        ];

        $result = $this->api->createThread($data);
        return decode($result);
    }

    /**
     * This will retrieve the thread.
     *
     * @param string $threadId
     * @return array|null
     */
    public function retrieve(
        string $threadId
    ): ?object
    {
        $result = $this->api->retrieveThread($threadId);
        return decode($result);
    }

    /**
     * This will modify the assistant.
     *
     * @param string $threadId
     * @param array $metadata
     * @return object|null
     */
    public function modify(
        string $threadId,
        array $metadata = []
    ): ?object
    {
        $data = [
            'metadata' => $metadata,
        ];
        $result = $this->api->modifyThread($threadId, $data);
        return decode($result);
    }

    /**
     * This will delete the assistant.
     *
     * @param string $threadId
     * @return object|null
     */
    public function delete(
        string $threadId
    ): ?object
    {
        $result = $this->api->deleteThread($threadId);
        return decode($result);
    }

    /**
     * This will get the assistant thread message handler.
     *
     * @param MessageHandler $handler
     * @return MessageHandler
     */
    public function message(
        string $handler = MessageHandler::class
    ): MessageHandler
    {
        return new $handler($this->apiKey);
    }
}