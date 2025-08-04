<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi\Handlers;

use Common\Controllers\OpenAi\Settings\CompletionSettings;

/**
 * Text Completion API Handler
 *
 * Manages interactions with OpenAI's Text Completion API,
 * enabling text generation based on prompts with configurable
 * parameters for controlling response behavior.
 *
 * @package Common\Controllers\OpenAi\Handlers
 */
class CompletionHandler extends Handler
{
	/**
	 * Configures text completion settings for API requests.
	 *
	 * @param string $prompt Input text to generate completions for
	 * @param object|null $systemSettings Optional system settings to customize behavior
	 * @param bool $stream Whether to stream the response (default: false)
	 * @return array Prepared settings for the API request
	 */
	protected function settings(
		string $prompt,
		?object $systemSettings = null,
		bool $stream = false
	): array
	{
		$settings = new CompletionSettings(
			model: $systemSettings->model() ?? null,
			prompt: $prompt,
			stream: $stream,
			temperature: $systemSettings->temperature() ?? null,
			frequencyPenalty: $systemSettings->frequency() ?? null,
			presencePenalty: $systemSettings->presence() ?? null,
			maxTokens: $systemSettings->maxTokens() ?? null
		);

		return $settings->get();
	}

	/**
	 * This will stream the response from the open ai api.
	 *
	 * @param string $prompt
	 * @param object|null $systemSettings
	 * @param callable|null $streamCallback
	 * @return void
	 */
	public function stream(
		string $prompt,
		?object $systemSettings = null,
		?callable $streamCallback = null
	): void
	{
		/**
		 * This will set up the settings.
		 */
		$stream = true;
		$settings = $this->settings($prompt, $systemSettings, $stream);

		/**
		 * This will stream the response.
		 *
		 * @SuppressWarnings PHP0406
		 */
		$this->api->completion(
			$settings,

			/**
			 * This will handle the response.
			 *
			 * @param resource $curl
			 * @param string $data
			 * @return integer
			 */
			function($curl, string $data) use ($streamCallback)
			{
				if (!isset($streamCallback))
				{
					return;
				}

				$streamCallback($curl, $data);
				return strlen($data);
			}
		);
	}

	/**
	 * This will gerneate a response from the open ai api.
	 *
	 * @param string $prompt
	 * @param object|null $systemSettings
	 * @param bool $stream
	 * @return object|null
	 */
	public function generate(
		string $prompt,
		?object $systemSettings = null
	): ?object
	{
		/**
		 * This will set up the settings.
		 */
		$settings = $this->settings($prompt, $systemSettings);

		/**
		 * This will get the response.
		 */
		$result = $this->api->completion($settings);
		return decode($result);
	}
}