<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi\Handlers;

/**
 * Content Moderation API Handler
 *
 * Manages interactions with OpenAI's Moderation API to detect
 * potentially harmful or inappropriate content across various categories.
 * Helps implement content filters and safety features.
 *
 * @package Common\Controllers\OpenAi\Handlers
 */
class ModerationHandler extends Handler
{
	/**
	 * Analyzes text content for potentially harmful categories.
	 *
	 * Evaluates text for harmful content in categories such as hate, harassment,
	 * self-harm, sexual content, and violence. Returns category scores and flags.
	 *
	 * @link https://platform.openai.com/docs/api-reference/moderations
	 * @param string $input Text content to analyze
	 * @param string $model Model to use for moderation (default: text-moderation-latest)
	 * @return object|null Moderation results with category flags or null on failure
	 */
	public function moderation(
		string $input,
		string $model = 'text-moderation-latest'
	): ?object
	{
		/**
		 * This will get the response.
		 */
		$result = $this->api->moderation([
			'input' => $input,
			'model' => $model
		]);
		return decode($result);
	}
}