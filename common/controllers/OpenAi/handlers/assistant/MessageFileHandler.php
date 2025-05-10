<?php declare(strict_types=1);
namespace App\Controllers\OpenAi\Handlers\Assistant;

use App\Controllers\OpenAi\Handlers\Handler;
use function App\Controllers\OpenAi\Handlers\decode;

/**
 * MessageFileHandler
 *
 * This will handle the assistant thread message files.
 *
 * @package App\Controllers\OpenAi\Handlers\Assistant
 */
class MessageFileHandler extends Handler
{
    /**
     * This will retrieve the message.
     *
     * @param string $threadId
     * @param string $messageId
     * @param string $fileId
     * @return array|null
     */
    public function retrieve(
        string $threadId,
        string $messageId,
        string $fileId
    ): ?object
    {
        $result = $this->api->retrieveMessageFile($threadId, $messageId, $fileId);
        return decode($result);
    }

    /**
     * This will list the assistants.
     *
     * @param string $threadId
     * @param string $messageId
     * @return object|null
     */
    public function list(
        string $threadId,
        string $messageId
    ): ?object
    {
        $query = ['limit' => 10];

        $result = $this->api->listMessageFiles($threadId, $messageId, $query);
        return decode($result);
    }
}