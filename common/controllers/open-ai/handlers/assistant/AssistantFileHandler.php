<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi\Handlers\Assistant;

use Common\Controllers\OpenAi\Handlers\Handler;
use function Common\Controllers\OpenAi\Handlers\decode;

/**
 * Class AssistantFileHandler
 *
 * Provides functionality to manage files for GPT assistants, including creation, retrieval, listing, and deletion.
 *
 * @package Common\Controllers\OpenAi\Handlers\Assistant
 */
class AssistantFileHandler extends Handler
{
	/**
	 * Creates a file resource for a given assistant.
	 *
	 * @param string $assistantId The unique identifier of the assistant.
	 * @param string $fileId The identifier of the file to attach to the assistant.
	 * @return object|null Decoded response object on success, or null on failure.
	 */
	public function create(
		string $assistantId,
		string $fileId
	): ?object
	{
		$result = $this->api->createAssistantFile($assistantId, $fileId);
		if (!$result)
		{
			return null;
		}

		return decode($result);
	}

	/**
	 * Retrieves details of an existing assistant file.
	 *
	 * @param string $assistantId The unique identifier of the assistant.
	 * @param string $fileId The identifier of the file to retrieve.
	 * @return object|null Decoded response object containing file details, or null if not found.
	 */
	public function retrieve(
		string $assistantId,
		string $fileId    ): ?object
	{
		$result = $this->api->retrieveAssistantFile($assistantId, $fileId);
		return decode($result);
	}

	/**
	 * Lists files attached to the specified assistant with an optional limit.
	 *
	 * @param string $assistantId The unique identifier of the assistant.
	 * @return object|null Decoded response containing a paginated list of files, or null on error.
	 */
	public function list(
		string $assistantId
	): ?object
	{
		$query = ['limit' => 10];

		$result = $this->api->listAssistantFiles($assistantId, $query);
		return decode($result);
	}

	/**
	 * Deletes a file from the assistant's resource list.
	 *
	 * @param string $assistantId The unique identifier of the assistant.
	 * @param string $fileId The identifier of the file to delete.
	 * @return object|null Decoded response confirming deletion, or null on error.
	 */
	public function delete(
		string $assistantId,
		string $fileId    ): ?object
	{
		$result = $this->api->deleteAssistantFile($assistantId, $fileId);
		return decode($result);
	}
}