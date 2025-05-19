<?php declare(strict_types=1);
namespace Common\Services\OpenAi\Chat\Handlers;

/**
 * AssistantChatHandler
 *
 * Handles the chat system content for the Assistant role in OpenAI's chat API.
 *
 * @package Common\Services\OpenAi\Chat\Handlers
 */
class AssistantChatHandler extends ChatHandler
{
	/**
	 * Returns the system content for the Assistant role.
	 *
	 * @return string
	 */
	public function getSystemContent(): string
	{
		return <<<EOT
You are a helpful assistant. Your role is to assist users by providing accurate and relevant information based on their queries. You should be polite, concise, and informative in your responses. Always strive to understand the user's intent and provide the best possible assistance.
EOT;
	}
}