<?php declare(strict_types=1);
namespace Common\Services\OpenAi\Chat;

use Common\Services\Service;
use Common\Controllers\OpenAi\OpenAi;
use Proto\Dispatch\ResponseTrait;
use Proto\Http\Loop\Message;

/**
 * ChatService
 *
 * This will handle the chat service.
 *
 * @package Common\Services\OpenAi\Chat
 */
class ChatService extends Service
{
    use ResponseTrait;

    /**
     * ChatService constructor.
     *
     * @param OpenAi $openAi The OpenAI instance to be used for chat operations.
     * @return void
     */
    public function __construct(
        protected OpenAi $openAi = new OpenAi()
    )
    {
    }

    /**
     * This will get the choice message.
     *
     * @param object $result
     */
    protected function getChoiceMessage(object $result): ?object
    {
        $message = $result->choices[0]->message ?? null;
        if (!isset($message))
        {
            return null;
        }

        return $message;
    }

    public function stream(
        string|array $content,
        string $type,
        mixed $settings = null,
        object $event,
        ?callable $callBack = null
    ): void
    {
        /**
         * This will set up the chat system content
         * to configure the chat params to return better
         * responses.
         */
        $systemSettings = HandlerFactory::get($type, $settings);
        if (!isset($systemSettings))
        {
            return;
        }

        /**
         * The content needs to be formatted to be
         * sent to the OpenAI API.
         */
        if (is_string($content))
        {
            $content = ContentHelper::format($content);
        }

        /**
         * This will stream the content.
         */
        $this->openAi->chat()->stream(
            $content,
            $systemSettings->getSystemContent(),
            $systemSettings,
            function($curl, $data) use ($event, $callBack)
            {
                /**
                 * This will check if there is an error.
                 */
                $result = json_decode($data);
                if (isset($result->error))
                {
                    error(
                        $result->error->message,
                        __FILE__,
                        __LINE__
                    );
                    return;
                }

                if (isset($callBack))
                {
                    $callBack($data);
                }

                /**
                 * Handles rending the response to the client.
                 */
                $formatted = true;
                new Message($data, $formatted);

                /**
                 * This will flush the buffer.
                 */
                if ( ob_get_level() > 0)
                {
                    ob_flush();
                }
                flush();
            }
        );
    }

    public function generate(
        string|array $content,
        string $type,
        mixed $settings = null
    ): object
    {
        /**
         * This will set up the chat system content
         * to configure the chat params to return better
         * responses.
         */
        $systemSettings = HandlerFactory::get($type, $settings);
        if (!isset($systemSettings))
        {
            return $this->error('No system content was returned.');
        }

        if (is_string($content))
        {
            $content = ContentHelper::format($content);
        }

        $result = $this->openAi->chat()->generate(
            $content,
            $systemSettings->getSystemContent(),
            $systemSettings
        );
        if (isset($result->error))
        {
            return $this->error($result->error->message);
        }

        $message = $this->getChoiceMessage($result);
        if (!isset($message))
        {
            return $this->error('No message was returned.');
        }

        return $message;
    }
}