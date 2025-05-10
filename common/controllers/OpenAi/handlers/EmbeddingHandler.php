<?php declare(strict_types=1);
namespace App\Controllers\OpenAi\Handlers;

/**
 * EmbeddingHandler
 *
 * This will handle the embeddings.
 *
 * @package App\Controllers\OpenAi\Handlers
 */
class EmbeddingHandler extends Handler
{
    /**
     * This will create the embeddings.
     *
     * @link https://platform.openai.com/docs/guides/embeddings/what-are-embeddings
     * @param string $input
     * @param string $model
     * @return object|null
     */
    public function create(
        string $input,
        string $model = 'text-embedding-ada-002'
    ): ?object
    {
        /**
         * This will get the response.
         */
        $result = $this->api->embeddings([
            'model' => $model,
            'input' => $input
        ]);
        return decode($result);
    }
}