<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi\Settings;

/**
 * Assistant API Configuration Settings
 *
 * Configures parameters for OpenAI Assistant API interactions.
 * Extends the base Settings class to provide specific configuration
 * for creating and managing AI assistants.
 *
 * @package Common\Controllers\OpenAi
 */
class AssistantSettings extends Settings
{
	/**
	 * Configures assistant creation and update settings.
	 *
	 * @param string $name Display name of the assistant
	 * @param string $description Brief description of the assistant's purpose
	 * @param string $instructions Detailed instructions for the assistant's behavior
	 * @param array $tools Tools the assistant can use (e.g., code_interpreter)
	 * @param array $file_ids File IDs to attach to the assistant
	 * @param string $model OpenAI model to use (defaults to gpt-3.5-turbo)
	 */
	public function __construct(
		protected string $name,
		protected string $description,
		protected string $instructions,
		protected array $tools = [],
		protected array $file_ids = [],
		protected string $model = 'gpt-3.5-turbo'
	)
	{
	}

	/**
	 * Returns the configured assistant settings as an array.
	 *
	 * @return array Formatted settings ready for API submission
	 */
	public function get(): array
	{
		return [
			'name' => $this->name,
			'description' => $this->description,
			'instructions' => $this->instructions,
			'tools' => $this->tools,
			'file_ids' => $this->file_ids,
			'model' => $this->model
		];
	}
}