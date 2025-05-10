<?php declare(strict_types=1);
namespace App\Controllers\OpenAi\Handlers;

/**
 * ModerationHandler
 *
 * This will handle moderations.
 *
 * @package App\Controllers\OpenAi\Handlers
 */
class ModerationHandler extends Handler
{
    /**
     * This will create the embeddings.
     *
     * @link https://platform.openai.com/docs/api-reference/moderations
     * @param string $input
     * @param string $model
     * @return object|null
     */
    public function moderation(
        string $input,
        string $model = 'text-moderation-latest'
    ): ?object
    {
        /**
         * This will get the response.
         */
        $result = $this->api->moderation([
            'input' => $input,
            'model' => $model
        ]);
        return decode($result);
    }
}