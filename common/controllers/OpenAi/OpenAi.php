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
 * OpenAi
 *
 * This will be the controller for the OpenAi integration.
 *
 * @package Common\Controllers\OpenAi
 */
class OpenAi extends Controller
{
    /**
     * This will set the api key.
     *
     * @param string|null $apiKey
     */
	public function __construct(
        protected ?string $apiKey = null
    )
    {
        parent::__construct();
        $this->getApiKey($apiKey);
    }

    /**
     * This will get the api key.
     *
     * @param string|null $apiKey
     * @return void
     */
    protected function getApiKey(?string $apiKey): void
    {
        $this->apiKey = $apiKey ?? env('apis')->openAi->key ?? null;
    }

    /**
     * This will get the chat handler.
     *
     * @param ChatHandler $handler
     * @return ChatHandler
     */
    public function chat(
        string $handler = ChatHandler::class
    ): ChatHandler
    {
        return new $handler($this->apiKey);
    }

    /**
     * This will get the completion handler.
     *
     * @param CompletionHandler $handler
     * @return CompletionHandler
     */
    public function completion(
        string $handler = CompletionHandler::class
    ): CompletionHandler
    {
        return new $handler($this->apiKey);
    }

    /**
     * This will get the embeddings handler.
     *
     * @param EmbeddingHandler $handler
     * @return EmbeddingHandler
     */
    public function embeddings(
        string $handler = EmbeddingHandler::class
    ): EmbeddingHandler
    {
        return new $handler($this->apiKey);
    }

    /**
     * This will get the audio handler.
     *
     * @param AudioHandler $handler
     * @return AudioHandler
     */
    public function audio(
        string $handler = AudioHandler::class
    ): AudioHandler
    {
        return new $handler($this->apiKey);
    }

    /**
     * This will get the files handler.
     *
     * @param FileHandler $handler
     * @return FileHandler
     */
    public function files(
        string $handler = FileHandler::class
    ): FileHandler
    {
        return new $handler($this->apiKey);
    }

    /**
     * This will get the files handler.
     *
     * @param FineTuneHandler $handler
     * @return FineTuneHandler
     */
    public function fineTune(
        string $handler = FineTuneHandler::class
    ): FineTuneHandler
    {
        return new $handler($this->apiKey);
    }

    /**
     * This will get the files handler.
     *
     * @param ImageHandler $handler
     * @return ImageHandler
     */
    public function image(
        string $handler = ImageHandler::class
    ): ImageHandler
    {
        return new $handler($this->apiKey);
    }

    /**
     * This will get the moderation handler.
     *
     * @param ModerationHandler $handler
     * @return ModerationHandler
     */
    public function moderation(
        string $handler = ModerationHandler::class
    ): ModerationHandler
    {
        return new $handler($this->apiKey);
    }

    /**
     * This will get the assitant handler.
     *
     * @param AssistantHandler $handler
     * @return AssistantHandler
     */
    public function assistant(
        string $handler = AssistantHandler::class
    ): AssistantHandler
    {
        return new $handler($this->apiKey);
    }
}