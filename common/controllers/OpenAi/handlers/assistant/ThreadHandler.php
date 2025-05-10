<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi\Handlers\Assistant;

use Common\Controllers\OpenAi\Handlers\Handler;
use function Common\Controllers\OpenAi\Handlers\decode;

/**
 * Thread Management for Assistant API
 *
 * Handles creation and management of conversation threads for OpenAI Assistants.
 * Threads represent ongoing conversations with users and maintain context
 * between interactions.
 *
 * @package Common\Controllers\OpenAi\Handlers\Assistant
 */
class ThreadHandler extends Handler
{
    /**
     * Creates a new conversation thread.
     *
     * Initializes a thread that can be used for interactions with assistants,
     * optionally with initial messages to establish context.
     *
     * @param array $messages Initial messages to add to the thread
     * @return object|null Thread object or null on failure
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