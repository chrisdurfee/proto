<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi;

use Proto\Controllers\Controller;
use Common\Controllers\OpenAi\Handlers\ChatHandler;
use Common\Controllers\OpenAi\Handlers\CompletionHandler;
use Common\Controllers\OpenAi\Handlers\EmbeddingHandler;
use Common\Controllers\OpenAi\Handlers\AudioHandler;
use Common\Controllers\OpenAi\Handlers\FileHandler;
use Common\Controllers\OpenAi\Handlers\FineTuneHandler;
use Common\Controllers\OpenAi\Handlers\ImageHandler;
use Common\Controllers\OpenAi\Handlers\ModerationHandler;
use Common\Controllers\OpenAi\Handlers\Assistant\AssistantHandler;

/**
 * OpenAI API Controller
 *
 * Main entry point for OpenAI API interactions. Provides unified access
 * to various API services like Chat, Embeddings, Images, and Assistants.
 * Handles authentication and delegates to specialized handlers.
 *
 * @package Common\Controllers\OpenAi
 */
class OpenAi extends Controller
{
    /**
	 * Initializes the OpenAI controller with an API key.
	 *
	 * @param string|null $apiKey API key or null to use environment settings
	 */
	public function __construct(
		protected ?string $apiKey = null
	)
	{
		parent::__construct();
		$this->getApiKey($apiKey);
	}
	/**
	 * Sets the API key from provided value or environment.
	 *
	 * @param string|null $apiKey API key or null to use environment variable
	 * @return void
	 */
	protected function getApiKey(?string $apiKey): void
	{
		$this->apiKey = $apiKey ?? env('apis')->openAi->key ?? null;
	}
	/**
	 * Provides access to Chat API functionalities.
	 *
	 * @param string $handler Optional handler class
	 * @return ChatHandler Configured chat handler instance
	 */
	public function chat(
		string $handler = ChatHandler::class
	): ChatHandler
	{
		return new $handler($this->apiKey);
	}
	/**
	 * Provides access to Text Completion API functionalities.
	 *
	 * @param string $handler Optional handler class
	 * @return CompletionHandler Configured completion handler instance
	 */
	public function completion(
		string $handler = CompletionHandler::class
	): CompletionHandler
	{
		return new $handler($this->apiKey);
	}
	/**
	 * Provides access to Embeddings API functionalities.
	 *
	 * @param string $handler Optional handler class
	 * @return EmbeddingHandler Configured embedding handler instance
	 */
	public function embeddings(
		string $handler = EmbeddingHandler::class
	): EmbeddingHandler
	{
		return new $handler($this->apiKey);
	}
	/**
	 * Provides access to Audio API functionalities.
	 *
	 * @param string $handler Optional handler class
	 * @return AudioHandler Configured audio handler instance
	 */
	public function audio(
		string $handler = AudioHandler::class
	): AudioHandler
	{
		return new $handler($this->apiKey);
	}
	/**
	 * Provides access to File Management API functionalities.
	 *
	 * @param string $handler Optional handler class
	 * @return FileHandler Configured file handler instance
	 */
	public function files(
		string $handler = FileHandler::class
	): FileHandler
	{
		return new $handler($this->apiKey);
	}
	/**
	 * Provides access to Fine-tuning API functionalities.
	 *
	 * @param string $handler Optional handler class
	 * @return FineTuneHandler Configured fine-tune handler instance
	 */
	public function fineTune(
		string $handler = FineTuneHandler::class
	): FineTuneHandler
	{
		return new $handler($this->apiKey);
	}
	/**
	 * Provides access to Image Generation API functionalities.
	 *
	 * @param string $handler Optional handler class
	 * @return ImageHandler Configured image handler instance
	 */
	public function image(
		string $handler = ImageHandler::class
	): ImageHandler
	{
		return new $handler($this->apiKey);
	}
	/**
	 * Provides access to Content Moderation API functionalities.
	 *
	 * @param string $handler Optional handler class
	 * @return ModerationHandler Configured moderation handler instance
	 */
	public function moderation(
		string $handler = ModerationHandler::class
	): ModerationHandler
	{
		return new $handler($this->apiKey);
	}
	/**
	 * Provides access to Assistants API functionalities.
	 *
	 * @param string $handler Optional handler class
	 * @return AssistantHandler Configured assistant handler instance
	 */
	public function assistant(
		string $handler = AssistantHandler::class
	): AssistantHandler
	{
		return new $handler($this->apiKey);
	}
}