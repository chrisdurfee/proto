<?php declare(strict_types=1);
namespace Proto\Dispatch\Drivers\Sms;

use Proto\Dispatch\Response;

/**
 * Interface TextDriverInterface
 *
 * This is the text driver interface. It is used to send text messages.
 *
 * @package Proto\Dispatch\Drivers\Sms
 */
interface TextDriverInterface
{
	/**
	 * Sends a text message.
	 *
	 * @param object $settings The text message settings.
	 * @return Response The response from sending the text.
	 */
	public function send(object $settings): Response;
}