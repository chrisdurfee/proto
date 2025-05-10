<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi\Handlers\Assistant;

use Common\Controllers\OpenAi\Settings\AssistantSettings;
use Common\Controllers\OpenAi\Handlers\Handler;
use function Common\Controllers\OpenAi\Handlers\decode;

/**
 * OpenAI Assistant API Handler
 *
 * Manages interactions with OpenAI's Assistant API, allowing the creation,
 * modification, and use of AI assistants with specific capabilities.
 *
 * @package Common\Controllers\OpenAi\Handlers\Assistant
 */
class AssistantHandler extends Handler
{
    /**
     * Configures assistant settings for API requests.
     *
     * @param string $name The name of the assistant
     * @param string $description Short description of the assistant's purpose
     * @param string $instructions Detailed instructions for the assistant's behavior
     * @param array $tools Array of tools the assistant can use (e.g., code_interpreter)
     * @param array $file_ids Array of file IDs to attach to the assistant
     * @param string $model OpenAI model to use (defaults to gpt-3.5-turbo)
     * @return array Prepared settings for the API request
     */
    protected function settings(
        string $name,
        string $description,
        string $instructions,
        array $tools = [],
        array $file_ids = [],
        string $model = 'gpt-3.5-turbo',
    ): array
    {
        $settings = new AssistantSettings(
            $name,
            $description,
            $instructions,
            $tools,
            $file_ids,
            $model
        );

        return $settings->get();
    }

    /**
     * This will create the assistant.
     *
     * @param string $name
     * @param string $description
     * @param string $instructions
     * @param array $tools
     * @param array $file_ids
     * @param string $model
     * @return object|null
     */
    public function create(
        string $name,
        string $description,
        string $instructions,
        array $tools = [],
        array $file_ids = [],
        string $model = 'gpt-3.5-turbo',
    ): ?object
    {
        /**
         * This will set up the settings.
         */
        $settings = $this->settings(
            $name,
            $description,
            $instructions,
            $tools,
            $file_ids,
            $model
        );

        /**
         * This will get the response.
         */
        $result = $this->api->createAssistant($settings);
        if (!$result)
        {
            return null;
        }

        return decode($result);
    }

    /**
     * This will retrieve the assistants.
     *
     * @param string $id
     * @return array|null
     */
    public function retrieve(
        string $id
    ): ?object
    {
        /**
         * This will get the response.
         */
        $result = $this->api->retrieveAssistant($id);
        return decode($result);
    }

    /**
     * This will list the assistants.
     *
     * @return object|null
     */
    public function list(): ?object
    {
        $query = ['limit' => 10];

        $result = $this->api->listAssistants($query);
        return decode($result);
    }

    /**
     * This will modify the assistant.
     *
     * @param string $id
     * @param array $data
     * @return object|null
     */
    public function modify(
        string $id,
        array $data = []
    ): ?object
    {
        /**
         * This will get the response.
         */
        $result = $this->api->modifyAssistant($id, $data);
        return decode($result);
    }

    /**
     * This will delete the assistant.
     *
     * @param string $id
     * @return object|null
     */
    public function delete(
        string $id
    ): ?object
    {
        /**
         * This will get the response.
         */
        $result = $this->api->deleteAssistant($id);
        return decode($result);
    }

    /**
     * This will get the assistant file handler.
     *
     * @param AssistantFileHandler $handler
     * @return AssistantFileHandler
     */
    public function file(
        string $handler = AssistantFileHandler::class
    ): AssistantFileHandler
    {
        return new $handler($this->apiKey);
    }
}