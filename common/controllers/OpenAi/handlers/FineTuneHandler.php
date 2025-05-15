<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi\Handlers;

/**
 * Fine-Tuning API Handler
 *
 * Manages interactions with OpenAI's Fine-Tuning API to create,
 * monitor, and manage custom models trained on specific datasets.
 * Enables more specialized AI behavior for specific use cases.
 *
 * @package Common\Controllers\OpenAi\Handlers
 */
class FineTuneHandler extends Handler
{
	/**
	 * Creates a new fine-tuning job.
	 *
	 * Starts the process of training a new model variant based on a provided
	 * training data file. The file must be previously uploaded via the Files API.
	 *
	 * @param string $file ID of the uploaded training data file
	 * @return object Response with fine-tuning job details
	 */
	public function create(
		string $file
	): object
	{
		$result = $this->api->createFineTune([
			'training_file' => $file
		]);
		return decode($result);
	}

	/**
	 * This will create a fine-tune.
	 *
	 * @return object
	 */
	public function list(): object
	{
		$result = $this->api->listFineTunes();
		return decode($result);
	}

	/**
	 * This will retrieve a fine-tune.
	 *
	 * @param string $file
	 * @return object
	 */
	public function retrieve(
		string $file
	): object
	{
		$result = $this->api->retrieveFineTune($file);
		return decode($result);
	}

	/**
	 * This will cancel a fine-tune.
	 *
	 * @param string $file
	 * @return object
	 */
	public function cancel(
		string $file
	): object
	{
		$result = $this->api->cancelFineTune($file);
		return decode($result);
	}

	/**
	 * This will retrieve a fine-tune.
	 *
	 * @param string $file
	 * @return object
	 */
	public function delete(
		string $file
	): object
	{
		$result = $this->api->deleteFineTune($file);
		return decode($result);
	}
}