<?php declare(strict_types=1);
namespace App\Controllers\OpenAi\Handlers\Assistant;

use App\Controllers\OpenAi\Settings\AssistantSettings;
use App\Controllers\OpenAi\Handlers\Handler;
use function App\Controllers\OpenAi\Handlers\decode;

/**
 * AssistantHandler
 *
 * This will handle the assistant.
 *
 * @package App\Controllers\OpenAi\Handlers\Assistant
 */
class AssistantHandler extends Handler
{
    /**
     * This will get the settings.
     *
     * @param string $name
     * @param string $description
     * @param string $instructions
     * @param array $tools
     * @param array $file_ids
     * @param string $model
     * @return array
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