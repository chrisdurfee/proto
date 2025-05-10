<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi\Settings;

/**
 * Chat Completion API Configuration
 *
 * Configures parameters for OpenAI Chat Completion API requests.
 * Handles model selection, message formatting, and response controls.
 *
 * @package Common\Controllers\OpenAi
 */
class ChatSettings extends Settings
{
    /**
     * Configures chat completion request parameters.
     *
     * @param string $model Model ID to use for completion (e.g., gpt-3.5-turbo)
     * @param array $messages Array of message objects with role and content
     * @param bool $stream Whether to stream the response incrementally
     * @param float $temperature Controls randomness (0-2, lower is more deterministic)
     * @param int $frequencyPenalty Reduces repetition of token sequences (-2 to 2)
     * @param int $presencePenalty Reduces repetition of topics (-2 to 2)
     * @param int $maxTokens Maximum tokens to generate in the completion
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