<?php declare(strict_types=1);
namespace App\Controllers\OpenAi\Handlers\Assistant;

use App\Controllers\OpenAi\Handlers\Handler;
use function App\Controllers\OpenAi\Handlers\decode;

/**
 * RunStepHandler
 *
 * This will handle the run thread step.
 *
 * @package App\Controllers\OpenAi\Handlers\Assistant
 */
class RunStepHandler extends Handler
{
    /**
     * This will retrieve the message.
     *
     * @param string $threadId
     * @param string $messageId
     * @param string $stepId
     * @return array|null
     */
    public function retrieve(
        string $threadId,
        string $runId,
        string $stepId
    ): ?object
    {
        $result = $this->api->retrieveRunStep($threadId, $runId, $stepId);
        return decode($result);
    }

    /**
     * This will list the assistants.
     *
     * @param string $threadId
     * @param string $runId
     * @return object|null
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