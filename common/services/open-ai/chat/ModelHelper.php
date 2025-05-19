<?php declare(strict_types=1);
namespace Common\Services\OpenAi\Chat;

/**
 * ModelHelper
 *
 * Determines which OpenAI model should be used based on provided settings.
 *
 * @package Common\Services\OpenAi\Chat
 */
class ModelHelper
{
	/**
	 * Returns the appropriate model identifier for the API request.
	 *
	 * @param object|null $settings Optional settings object that may include a model name.
	 * @return string The OpenAI model identifier.
	 */
	public static function get(?object $settings = null): string
	{
		$model = $settings->model ?? null;
		return match ($model)
		{
			'gpt-4.1', 'gpt-4.1-mini', 'gpt-4.1-nano' => $model,
			'gpt-4o', 'gpt-4' => 'gpt-4o',
			'gpt-4o-mini' => 'gpt-4o-mini',
			'gpt-4o-mini-high' => 'gpt-4o-mini-high',
			'gpt-3.5', 'gpt-3.5-turbo' => 'gpt-3.5-turbo',
			'gpt-3.5-turbo-16k' => 'gpt-3.5-turbo-16k',
			'gpt-4.5' => 'gpt-4.5',
			default => 'gpt-4o'
		};
	}
}
