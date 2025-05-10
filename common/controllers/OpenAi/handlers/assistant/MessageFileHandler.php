<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi\Handlers\Assistant;

use Common\Controllers\OpenAi\Handlers\Handler;
use function Common\Controllers\OpenAi\Handlers\decode;

/**
 * Message File Management for Assistant API
 *
 * Handles operations for files attached to messages in assistant threads,
 * including retrieving details and listing attached files.
 *
 * @package Common\Controllers\OpenAi\Handlers\Assistant
 */
class MessageFileHandler extends Handler
{
    /**
     * Retrieves details of a specific file attached to a message.
     *
     * @param string $threadId ID of the thread containing the message
     * @param string $messageId ID of the message with the attached file
     * @param string $fileId ID of the file to retrieve
     * @return object|null File details or null on failure
     */
    public function retrieve(
        string $threadId,
        string $messageId,
        string $fileId
    ): ?object
    {
        $result = $this->api->retrieveMessageFile($threadId, $messageId, $fileId);
        return decode($result);
    }    /**
     * Lists all files attached to a specific message.
     *
     * @param string $threadId ID of the thread containing the message
     * @param string $messageId ID of the message to list files for
     * @return object|null List of files or null on failure
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