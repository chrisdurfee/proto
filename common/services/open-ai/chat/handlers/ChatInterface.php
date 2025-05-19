<?php declare(strict_types=1);
namespace Common\Services\OpenAi\Chat\Handlers;

/**
 * ChatInterface
 *
 * Defines the required methods for all chat handlers.
 *
 * @package Common\Services\OpenAi\Chat\Handlers
 */
interface ChatInterface
{
	/**
	 * Returns the system prompt content.
	 *
	 * @return string
	 */
	public function getSystemContent(): string;

	/**
	 * Returns the model identifier (e.g., gpt-4).
	 *
	 * @return string
	 */
	public function model(): string;
}
