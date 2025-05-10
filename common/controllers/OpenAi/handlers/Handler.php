<?php declare(strict_types=1);
namespace App\Controllers\OpenAi\Handlers;

require_once __DIR__ . '/../../../../vendor/autoload.php';

use Orhanerday\OpenAi\OpenAi;
use Dashr\Utils\Format\JsonFormat;

/**
 * This will decode the data.
 *
 * @param mixed $data
 * @return mixed
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
 * Handler
 *
 * This will be the base handler for open ai.
 *
 * @package App\Controllers\OpenAi\Handlers
 */
abstract class Handler
{
    /**
     * This will store the integration.
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