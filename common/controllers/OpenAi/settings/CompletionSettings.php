<?php declare(strict_types=1);
namespace App\Controllers\OpenAi\Settings;

/**
 * CompletionSettings
 *
 * This will be the completion settings for the OpenAi integration.
 *
 * @package App\Controllers\OpenAi
 */
class CompletionSettings extends Settings
{
    /**
     * This will set the request settings.
     *
     * @param string $model
     * @param string $prompt
     * @param bool $stream
     * @param float $temperature
     * @param int $frequencyPenalty
     * @param int $presencePenalty
     * @param int $maxTokens
     * @return void
     */
	public function __construct(
        protected string $model = 'gpt-3.5-turbo',
        protected string $prompt = '',
        protected bool $stream = false,
        protected float $temperature = 1.0,
        protected int $frequencyPenalty = 0,
        protected int $presencePenalty = 0,
        protected int $maxTokens = 2000
    )
    {
    }

    /**
     * This will get the settings.
     *
     * @return array
     */
    public function get(): array
    {
        return [
            'model' => $this->model,
            'prompt' => $this->prompt,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'frequency_penalty' => $this->frequencyPenalty,
            'presence_penalty' => $this->presencePenalty,
            "stream" => $this->stream
        ];
    }
}