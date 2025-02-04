<?php declare(strict_types=1);
namespace Proto\Dispatch\Drivers\Sms;

use Proto\Controllers\Sms\ZipwhipController;
use Proto\Dispatch\Response;

/**
 * ZipwhipDriver
 *
 * This is the zipwhip driver class. This will be used to send text messages.
 *
 * @package Proto\Dispatch\Drivers\Sms
 */
class ZipwhipDriver extends TextDriver
{
    /**
     * This will create a new ZipwhipDriver object.
     *
     * @param ZipwhipController $controller
     * @return void
     */
    public function __construct(
		private ZipwhipController $controller = new ZipwhipController()
	)
    {
    }

    /**
     * This will send a message.
     *
     * @param object $settings
     * @return bool|object
     */
    protected function sendMessage(object $settings): bool|object
    {
        return $this->controller->send($settings->session, $settings->to, $settings->message);
    }

    /**
     * This will send the text.
     *
     * @param object $settings
     * @return Response
     */
    public function send(object $settings): Response
    {
        if (empty($settings->session))
        {
            return $this->error('No client phone number found.');
        }

        if (empty($settings->to))
        {
            return $this->error('no contact number setup');
        }

        $result = $this->sendMessage($settings);
        if (!$result)
        {
            return $this->error('The text failed to send.');
        }

        return $this->response(false, 'Text message sent.', $result);
    }
}
