<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi\Handlers\Assistant;

use Common\Controllers\OpenAi\Handlers\Handler;
use function Common\Controllers\OpenAi\Handlers\decode;

/**
 * Run Management for Assistant API
 *
 * Handles the execution and management of assistant runs on threads.
 * Runs represent the process of an assistant analyzing and responding
 * to messages in a conversation thread.
 *
 * @package Common\Controllers\OpenAi\Handlers\Assistant
 */
class RunHandler extends Handler
{
    /**
     * Initiates an assistant run on a conversation thread.
     *
     * Starts the process of an assistant analyzing and responding to
     * messages in the specified thread.
     *
     * @param string $threadId ID of the thread to run the assistant on
     * @param array $data Configuration data including assistant ID and options
     * @return object|null Run object or null on failure
     */
    public function create(
        string $threadId,
        array $data = []
    ): ?object
    {
        $result = $this->api->createRun($threadId, $data);
        return decode($result);
    }

    /**
     * This will retrieve the run.
     *
     * @param string $threadId
     * @param string $runId
     * @return array|null
     */
    public function retrieve(
        string $threadId,
        string $runId
    ): ?object
    {
        $result = $this->api->retrieveRun($threadId, $runId);
        return decode($result);
    }

    /**
     * This will list the runs.
     *
     * @param string $threadId
     * @return object|null
     */
    public function list(
        string $threadId
    ): ?object
    {
        $query = ['limit' => 10];

        $result = $this->api->listRuns($threadId, $query);
        return decode($result);
    }

    /**
     * This will modify the assistant.
     *
     * @param string $threadId
     * @param string $runId
     * @param array $metadata
     * @return object|null
     */
    public function modify(
        string $threadId,
        string $runId,
        array $metadata = []
    ): ?object
    {
        $data = [
            'metadata' => $metadata,
        ];
        $result = $this->api->modifyRun($threadId, $runId, $data);
        return decode($result);
    }

    /**
     * This will submit the tool outputs.
     *
     * @param string $threadId
     * @param string $runId
     * @param array $toolOutputs
     * @return object|null
     */
    public function submitToolOutputs(
        string $threadId,
        string $runId,
        array $toolOutputs = []
    ): ?object
    {
        $outputs = [
            'tool_outputs' => $toolOutputs
        ];
        $result = $this->api->submitToolOutputs($threadId, $runId, $outputs);
        return decode($result);
    }

    /**
     * This will delete the assistant.
     *
     * @param string $threadId
     * @param string $runId
     * @return object|null
     */
    public function cancel(
        string $threadId,
        string $runId
    ): ?object
    {
        $result = $this->api->cancelRun($threadId, $runId);
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