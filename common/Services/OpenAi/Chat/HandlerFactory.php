<?php declare(strict_types=1);
namespace Common\Services\OpenAi\Chat;

use Common\Services\OpenAI\Chat\Handlers\ChatHandler;

/**
 * HandlerFactory
 *
 * Dynamically creates an instance of a chat handler if it exists.
 *
 * This factory looks for handler classes under the Handlers namespace
 * and initializes them if the corresponding class file is found and valid.
 *
 * @package Common\Services\OpenAi\Chat
 */
class HandlerFactory
{
	/**
	 * Returns an instance of the chat handler for the given type if the handler class exists.
	 *
	 * @param string $type The type of chat handler (e.g., "TicketReply", "Code").
	 * @param mixed $settings Optional configuration or dependencies for the handler.
	 * @return ChatHandler|null A chat handler instance or null if the handler doesn't exist.
	 */
	public static function get(string $type, mixed $settings): ?ChatHandler
	{
		$class = __NAMESPACE__ . '\\Handlers\\' . $type . 'Handler';
		$path = __DIR__ . '/Handlers/' . $type . 'Handler.php';

		// Only initialize the class if the file and class exist
		if (!file_exists($path) || !class_exists($class))
		{
			return null;
		}

		return new $class($settings);
	}
}