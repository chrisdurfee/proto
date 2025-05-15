<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi\Handlers;

/**
 * Files API Handler
 *
 * Manages interactions with OpenAI's Files API for uploading,
 * listing, retrieving, and deleting files used with OpenAI services.
 * Particularly useful for fine-tuning and assistants.
 *
 * @package Common\Controllers\OpenAi\Handlers
 */
class FileHandler extends Handler
{
	use CurlFileTrait;

	/**
	 * Lists all files uploaded to OpenAI.
	 *
	 * Retrieves a list of files that belong to the user's organization
	 * with their metadata.
	 *
	 * @return object|null List of files or null on failure
	 */
	public function list(): ?object
	{
		/**
		 * This will get the response.
		 */
		$result = $this->api->listFiles();
		return decode($result);
	}

	/**
	 * This will upload a file.
	 *
	 * @param string $file
	 * @param string $purpose
	 * @return object|null
	 */
	public function upload(
		string $file,
		string $purpose = 'answers'
	): ?object
	{
		$file = $this->createCurlFile($file);

		/**
		 * This will get the response.
		 */
		$result = $this->api->uploadFile([
			'purpose' => $purpose,
			'file' => $file
		]);
		return decode($result);
	}

	/**
	 * This will delete a file.
	 *
	 * @param string $file
	 * @return object|null
	 */
	public function delete(
		string $file
	): ?object
	{
		/**
		 * This will get the response.
		 */
		$result = $this->api->deleteFile([
			'file' => $file
		]);
		return decode($result);
	}

	/**
	 * This will retrieve a file.
	 *
	 * @param string $file
	 * @return object|null
	 */
	public function retrieve(
		string $file
	): ?object
	{
		/**
		 * This will get the response.
		 */
		$result = $this->api->retrieveFile([
			'file' => $file
		]);
		return decode($result);
	}
}