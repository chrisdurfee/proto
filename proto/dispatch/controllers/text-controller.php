<?php declare(strict_types=1);
namespace Proto\Dispatch\Controllers;

use Proto\Dispatch\Text\Template;
use Proto\Dispatch\Sms;
use Proto\Dispatch\Response;

/**
 * TextController
 *
 * This will be the base controller for all text dispatch controllers.
 *
 * @package Proto\Dispatch\Controllers
 */
class TextController extends Controller
{
    /**
     * This will create a text template.
     *
     * @param string $template
     * @param object|null $data
     * @return string
     */
    protected static function createText(string $template, ?object $data = null): string
    {
        return (string)Template::create($template, $data);
    }

    /**
     * This will setup a text to queue.
     *
     * @param object $settings
     * @param object|null $data
     * @return object
     */
    public static function enqueue(object $settings, ?object $data = null): object
    {
        $template = self::createText($settings->template, $data);

        return (object)[
            'recipient' => $settings->to,
            'session' => $settings->session,
            'message' => (string)$template
        ];
    }

    /**
     * This will send a text.
     *
     * @param object $settings
     * @param object|null $data
     * @return Response
     */
    public static function dispatch(object $settings, ?object $data = null): Response
    {
        if (isset($settings->compiledTemplate) && !empty($settings->subject))
        {
            $settings->message = $settings->compiledTemplate;
        }
        else if (isset($settings->template))
        {
            $settings->message = self::createText($settings->template, $data);
        }

        if (isset($settings->subject) && !empty($settings->subject))
        {
            $settings->message = $settings->subject . ' - ' . $settings->message;
        }

        /**
         * A custom driver can be used to override the default driver.
         */
        $customDriver = $settings->customDriver ?? null;
        return self::send(new Sms($settings, $customDriver));
    }
}