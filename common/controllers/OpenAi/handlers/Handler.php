<?php declare(strict_types=1);
namespace Common\Controllers\OpenAi\Handlers;

require_once __DIR__ . '/../../../../vendor/autoload.php';

use Orhanerday\OpenAi\OpenAi;
use Proto\Utils\Format\JsonFormat;

/**
 * Decodes JSON data received from OpenAI API responses.
 *
 * @param mixed $data The data to decode
 * @return mixed Decoded data or false on failure
 */
function decode(mixed $data): mixed
{
    if ($data === false)
    {
        return false;
    }

    return JsonFormat::decode($data);
}

/**
 * Base Handler for OpenAI API Interactions
 *
 * Provides common functionality for all OpenAI API service handlers,
 * including authentication and request handling.
 *
 * @package Common\Controllers\OpenAi\Handlers
 */
abstract class Handler
{
    /**
     * The OpenAI API client instance.
     *
     * @var OpenAi $api
     */
    protected OpenAi $api;

	/**
     * This will set the api key.
     *
     * @param string $apiKey
     * @param OpenAi $integration
     * @return void
     */
	public function __construct(
        protected string $apiKey,
        protected $integration = OpenAi::class
    )
    {
        $this->api = new $integration($this->apiKey);
    }

    /**
     * This will get the system content.
     *
     * @return array
     */
    protected function getSystemContent(?string $systemContent = null): array
    {
        return [
            "role" => "system",
            "content" => $systemContent ?? "You are a helpful assistant."
        ];
    }
}