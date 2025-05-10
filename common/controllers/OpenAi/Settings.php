<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi;

/**
 * OpenAI API Configuration Settings
 *
 * Configures parameters for OpenAI API requests with support
 * for model selection, message formatting, and response controls.
 *
 * @package Common\Controllers\OpenAi
 */
class Settings
{    /**
     * Configures API request parameters.
     *
     * @param string $model OpenAI model ID to use
     * @param array $messages Array of conversation messages
     * @param boolean $stream Whether to stream the response
     * @param float $temperature Controls randomness (0-2)
     * @param integer $frequencyPenalty Reduces repetition of tokens (-2 to 2)
     * @param integer $presencePenalty Reduces topic repetition (-2 to 2)
     * @param integer $maxTokens Maximum tokens to generate
     */
	public function __construct(
        protected string $model = 'gpt-3.5-turbo',
        protected array $messages = [],
        protected bool $stream = false,
        protected float $temperature = 1.0,
        protected int $frequencyPenalty = 0,
        protected int $presencePenalty = 0,
        protected int $maxTokens = 2000
    )
    {
    }    /**
     * Returns settings as an API-ready array.
     *
     * @return array Formatted settings for API request
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