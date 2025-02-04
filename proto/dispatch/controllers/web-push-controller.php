<?php declare(strict_types=1);
namespace Proto\Dispatch\Controllers;

use Proto\Dispatch\Push\Template;
use Proto\Dispatch\WebPush;
use Proto\Dispatch\Response;

/**
 * WebPushController
 *
 * This will be the controller for web push notifications.
 *
 * @package Proto\Dispatch\Controllers
 */
class WebPushController extends Controller
{
    /**
     * This will create a push template.
     *
     * @param string $template
     * @param object|null $data
     * @return string
     */
    protected static function createPush(string $template, ?object $data = null): string
    {
        return (string)Template::create($template, $data);
    }

    /**
     * This will setup a push notificiation to queue.
     *
     * @param object $settings
     * @param object|null $data
     * @return object
     */
    public static function enqueue(object $settings, ?object $data = null): object
    {
        $template = self::createPush($settings->template, $data);

        return (object)[
            'subscriptions' => $settings->subscriptions,
            'message' => (string)$template
        ];
    }

    /**
     * This will send an email.
     *
     * @param object $settings
     * @param object|null $data
     * @return Response
     */
    public static function dispatch(object $settings, ?object $data = null): Response
    {
        $template = $settings->compiledTemplate ?? self::createPush($settings->template, $data);
        return self::send(new WebPush($settings->subscriptions, $template));
    }
}