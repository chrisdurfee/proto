<?php declare(strict_types=1);
namespace App\Controllers\OpenAi\Settings;

/**
 * ChatSettings
 *
 * This will be the chat settings for the OpenAi integration.
 *
 * @package App\Controllers\OpenAi
 */
class ChatSettings extends Settings
{
    /**
     * This will set the request settings.
     *
     * @param string $model
     * @param array $messages
     * @param bool $stream
     * @param float $temperature
     * @param int $frequencyPenalty
     * @param int $presencePenalty
     * @param int $maxTokens
     * @return void
     */
	public function __construct(
        protected string $model = 'gpt-3.5-turbo',
        protected array $messages = [],
        protected bool $stream = false,
        protected float $temperature = 1.0,
        protected int $frequencyPenalty = 0,
        protected int $presencePenalty = 0,
        protected int $maxTokens = 1000
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
            'messages' => $this->messages,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'frequency_penalty' => $this->frequencyPenalty,
            'presence_penalty' => $this->presencePenalty,
            "stream" => $this->stream
        ];
    }
}