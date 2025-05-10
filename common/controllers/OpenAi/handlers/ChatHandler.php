<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi\Handlers;

use Common\Controllers\OpenAi\Settings\ChatSettings;

/**
 * Chat Completion API Handler
 *
 * Manages interactions with OpenAI's Chat Completion API,
 * enabling conversational AI capabilities with support for
 * various models, streaming responses, and conversation history.
 *
 * @package Common\Controllers\OpenAi\Handlers
 */
class ChatHandler extends Handler
{
    /**
     * Configures chat completion settings for API requests.
     *
     * @param array $messages Array of message objects with role and content
     * @param object|null $systemSettings Optional system settings to customize behavior
     * @param bool $stream Whether to stream the response (default: false)
     * @return array Prepared settings for the API request
     */
    protected function settings(
        array $messages,
        ?object $systemSettings = null,
        bool $stream = false
    ): array
    {
        $settings = new ChatSettings(
            model: $systemSettings->model() ?? null,
            messages: $messages,
            stream: $stream,
            temperature: $systemSettings->temperature() ?? null,
            frequencyPenalty: $systemSettings->frequency() ?? null,
            presencePenalty: $systemSettings->presence() ?? null,
            maxTokens: $systemSettings->maxTokens() ?? null
        );

        return $settings->get();
    }

    /**
     * This will get the prompts.
     *
     * @param string|null $systemContent
     * @param string|array $prompt
     * @return array
     */
    protected function setupMessages(?string $systemContent, string|array $prompt): array
    {
        /**
         * This will set up the chat messages.
         */
        $messages = [
            $this->getSystemContent($systemContent)
        ];

        if (is_array($prompt))
        {
            $messages = array_merge($messages, $prompt);
        }
        else
        {
            $messages[] = [
                "role" => "user",
                "content" => $prompt
            ];
        }

        return $messages;
    }

	/**
     * This will stream the response from the open ai api.
     *
     * @param string|array $prompt
     * @param string|null $systemContent
     * @param string|null $model
     * @param callable|null $streamCallback
     * @return void
     */
	public function stream(
        string|array $prompt,
        ?string $systemContent = null,
        ?object $systemSettings = null,
        ?callable $streamCallback = null
    ): void
    {
        /**
         * This will set up the chat messages.
         */
        $messages = $this->setupMessages($systemContent, $prompt);

        /**
         * This will set up the settings.
         */
        $stream = true;
        $settings = $this->settings($messages, $systemSettings, $stream);

        /**
         * This will stream the response.
         *
         * @SuppressWarnings PHP0406
         */
        $this->api->chat(
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
     * @param string|array $prompt
     * @param string|null $systemContent
     * @param string|null $model
     * @param bool $stream
     * @return object|null
     */
	public function generate(
        string|array $prompt,
        ?string $systemContent = null,
        ?object $systemSettings = null
    ): ?object
    {
        /**
         * This will set up the chat messages.
         */
        $messages = $this->setupMessages($systemContent, $prompt);

        /**
         * This will set up the settings.
         */
        $settings = $this->settings($messages, $systemSettings);

        /**
         * This will get the response.
         */
        $result = $this->api->chat($settings);
        if (!$result)
        {
            return null;
        }

        return decode($result);
    }
}