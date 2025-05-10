<?php declare(strict_types=1);
namespace App\Controllers\OpenAi\Settings;

/**
 * AssistantSettings
 *
 * This will be the assistant settings for the OpenAi integration.
 *
 * @package App\Controllers\OpenAi
 */
class AssistantSettings extends Settings
{
    /**
     * This will set the request settings.
     *
     * @param string $name
     * @param string $description
     * @param string $instructions
     * @param array $tools
     * @param array $file_ids
     * @param string $model
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
     * This will get the settings.
     *
     * @return array
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