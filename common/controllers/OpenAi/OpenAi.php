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
 * Class OpenAi
 *
 * Main entry point for interacting with the OpenAI API.
 * This controller aggregates various service handlers (e.g., Chat, Embeddings, Audio)
 * to provide a unified interface for OpenAI functionalities. It manages API key
 * authentication and delegates requests to the appropriate handlers.
 *
 * @package Common\Controllers\OpenAi
 */
class OpenAi extends Controller
{
	/**
	 * Constructor for the OpenAi controller.
	 * Initializes the API key by first checking the provided argument,
	 * then an environment variable (`env('apis')->openAi->key`), and finally
	 * defaulting to null if not found. It calls the parent constructor and then `getApiKey`.
	 *
	 * @param string|null $apiKey The OpenAI API key. If null, it attempts to load from environment variables.
	 */
	public function __construct(
		protected ?string $apiKey = null
	)
	{
		parent::__construct();
		$this->getApiKey($apiKey);
	}

	/**
	 * Retrieves and sets the API key for the controller instance.
	 * It prioritizes the `$apiKey` argument, falls back to the 'openAi.key'
	 * from environment settings (`env('apis')->openAi->key`), and ultimately
	 * allows a null key if neither is available. This method is called during construction.
	 *
	 * @param string|null $apiKey The API key to use. If null, attempts to use environment variable.
	 * @return void
	 */
	protected function getApiKey(?string $apiKey): void
	{
		$this->apiKey = $apiKey ?? env('apis')->openAi->key ?? null;
	}

	/**
	 * Provides access to the Chat API functionalities.
	 * Instantiates and returns a ChatHandler (or a specified custom handler) initialized with the API key.
	 * This handler is responsible for all chat completion requests to OpenAI.
	 *
	 * @param string $handler Optional: The fully qualified class name of the chat handler to instantiate. Defaults to `ChatHandler::class`.
	 * @return ChatHandler An instance of the chat handler, configured with the current API key.
	 */
	public function chat(
		string $handler = ChatHandler::class
	): ChatHandler
	{
		return new $handler($this->apiKey);
	}

	/**
	 * Provides access to the Text Completion API functionalities.
	 * Instantiates and returns a CompletionHandler (or a specified custom handler) initialized with the API key.
	 * This handler is responsible for all text completion requests to OpenAI.
	 *
	 * @param string $handler Optional: The fully qualified class name of the completion handler to instantiate. Defaults to `CompletionHandler::class`.
	 * @return CompletionHandler An instance of the completion handler, configured with the current API key.
	 */
	public function completion(
		string $handler = CompletionHandler::class
	): CompletionHandler
	{
		return new $handler($this->apiKey);
	}

	/**
	 * Provides access to the Embeddings API functionalities.
	 * Instantiates and returns an EmbeddingHandler (or a specified custom handler) initialized with the API key.
	 * This handler is responsible for creating text embeddings via OpenAI.
	 *
	 * @param string $handler Optional: The fully qualified class name of the embedding handler to instantiate. Defaults to `EmbeddingHandler::class`.
	 * @return EmbeddingHandler An instance of the embedding handler, configured with the current API key.
	 */
	public function embeddings(
		string $handler = EmbeddingHandler::class
	): EmbeddingHandler
	{
		return new $handler($this->apiKey);
	}

	/**
	 * Provides access to the Audio API functionalities (transcription, translation).
	 * Instantiates and returns an AudioHandler (or a specified custom handler) initialized with the API key.
	 * This handler is responsible for all audio processing requests to OpenAI.
	 *
	 * @param string $handler Optional: The fully qualified class name of the audio handler to instantiate. Defaults to `AudioHandler::class`.
	 * @return AudioHandler An instance of the audio handler, configured with the current API key.
	 */
	public function audio(
		string $handler = AudioHandler::class
	): AudioHandler
	{
		return new $handler($this->apiKey);
	}

	/**
	 * Provides access to the File Management API functionalities.
	 * Instantiates and returns a FileHandler (or a specified custom handler) initialized with the API key.
	 * This handler is responsible for managing files (upload, list, delete) on OpenAI.
	 *
	 * @param string $handler Optional: The fully qualified class name of the file handler to instantiate. Defaults to `FileHandler::class`.
	 * @return FileHandler An instance of the file handler, configured with the current API key.
	 */
	public function files(
		string $handler = FileHandler::class
	): FileHandler
	{
		return new $handler($this->apiKey);
	}

	/**
	 * Provides access to the Fine-tuning API functionalities.
	 * Instantiates and returns a FineTuneHandler (or a specified custom handler) initialized with the API key.
	 * This handler is responsible for managing fine-tuning jobs with OpenAI.
	 *
	 * @param string $handler Optional: The fully qualified class name of the fine-tune handler to instantiate. Defaults to `FineTuneHandler::class`.
	 * @return FineTuneHandler An instance of the fine-tune handler, configured with the current API key.
	 */
	public function fineTune(
		string $handler = FineTuneHandler::class
	): FineTuneHandler
	{
		return new $handler($this->apiKey);
	}

	/**
	 * Provides access to the Image Generation API functionalities.
	 * Instantiates and returns an ImageHandler (or a specified custom handler) initialized with the API key.
	 * This handler is responsible for generating and manipulating images using OpenAI.
	 *
	 * @param string $handler Optional: The fully qualified class name of the image handler to instantiate. Defaults to `ImageHandler::class`.
	 * @return ImageHandler An instance of the image handler, configured with the current API key.
	 */
	public function image(
		string $handler = ImageHandler::class
	): ImageHandler
	{
		return new $handler($this->apiKey);
	}

	/**
	 * Provides access to the Content Moderation API functionalities.
	 * Instantiates and returns a ModerationHandler (or a specified custom handler) initialized with the API key.
	 * This handler is responsible for content moderation requests to OpenAI.
	 *
	 * @param string $handler Optional: The fully qualified class name of the moderation handler to instantiate. Defaults to `ModerationHandler::class`.
	 * @return ModerationHandler An instance of the moderation handler, configured with the current API key.
	 */
	public function moderation(
		string $handler = ModerationHandler::class
	): ModerationHandler
	{
		return new $handler($this->apiKey);
	}

	/**
	 * Provides access to the Assistants API functionalities.
	 * Instantiates and returns an AssistantHandler (or a specified custom handler) initialized with the API key.
	 * This handler is responsible for interactions with OpenAI Assistants.
	 *
	 * @param string $handler Optional: The fully qualified class name of the assistant handler to instantiate. Defaults to `AssistantHandler::class`.
	 * @return AssistantHandler An instance of the assistant handler, configured with the current API key.
	 */
	public function assistant(
		string $handler = AssistantHandler::class
	): AssistantHandler
	{
		return new $handler($this->apiKey);
	}
}