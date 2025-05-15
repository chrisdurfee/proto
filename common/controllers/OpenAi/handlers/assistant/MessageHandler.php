<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi\Handlers\Assistant;

use Common\Controllers\OpenAi\Handlers\Handler;
use function Common\Controllers\OpenAi\Handlers\decode;

/**
 * Thread Message Management for Assistant API
 *
 * Manages creation, retrieval, and manipulation of messages within
 * Assistant conversation threads. Messages represent individual
 * communications between users and assistants.
 *
 * @package Common\Controllers\OpenAi\Handlers\Assistant
 */
class MessageHandler extends Handler
{
	/**
	 * Creates a new message in a conversation thread.
	 *
	 * Adds a user message to the specified thread with optional attachments
	 * and metadata.
	 *
	 * @param string $threadId ID of the thread to add the message to
	 * @param array $data Message content and metadata
	 * @return object|null Message object or null on failure
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