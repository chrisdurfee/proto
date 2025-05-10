<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi\Handlers\Assistant;

use Common\Controllers\OpenAi\Handlers\Handler;
use function Common\Controllers\OpenAi\Handlers\decode;

/**
 * Run Step Management for Assistant API
 *
 * Handles the individual steps that occur during an assistant run.
 * Steps provide detailed information about what actions the assistant
 * is taking when processing messages in a thread.
 *
 * @package Common\Controllers\OpenAi\Handlers\Assistant
 */
class RunStepHandler extends Handler
{
    /**
     * Retrieves a specific step from a run.
     *
     * Gets detailed information about an individual step that occurred
     * during the execution of an assistant run on a thread.
     *
     * @param string $threadId ID of the conversation thread
     * @param string $runId ID of the run
     * @param string $stepId ID of the step to retrieve
     * @return object|null Step object or null on failure
     */
    public function retrieve(
        string $threadId,
        string $runId,
        string $stepId
    ): ?object
    {
        $result = $this->api->retrieveRunStep($threadId, $runId, $stepId);
        return decode($result);
    }    /**
     * Lists all steps for a specific run.
     *
     * Retrieves information about all steps that occurred during
     * the execution of an assistant run on a thread.
     *
     * @param string $threadId ID of the conversation thread
     * @param string $runId ID of the run to list steps for
     * @return object|null List of steps or null on failure
     */
    public function list(
        string $threadId,
        string $runId
    ): ?object
    {
        $query = ['limit' => 10];

        $result = $this->api->listRunSteps($threadId, $runId, $query);
        return decode($result);
    }
}