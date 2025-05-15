<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi\Handlers;

/**
 * Embeddings API Handler
 *
 * Manages interactions with OpenAI's Embeddings API for creating
 * vector representations of text that capture semantic meaning,
 * useful for search, clustering, and recommendations.
 *
 * @package Common\Controllers\OpenAi\Handlers
 */
class EmbeddingHandler extends Handler
{
	/**
	 * Creates vector embeddings from input text.
	 *
	 * Vector embeddings capture the semantic meaning of text and can be used
	 * for search, recommendations, clustering, and other machine learning tasks.
	 *
	 * @link https://platform.openai.com/docs/guides/embeddings/what-are-embeddings
	 * @param string $input Text to convert to an embedding
	 * @param string $model Model to use for embedding generation (default: text-embedding-ada-002)
	 * @return object|null Response containing the embedding vectors or null on failure
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