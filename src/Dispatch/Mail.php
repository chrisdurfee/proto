<?php declare(strict_types=1);
namespace Proto\Dispatch;

/**
 * Class Mail
 *
 * Placeholder class to support sending mail.
 *
 * @package Proto\Dispatch
 */
class Mail extends Dispatch
{
	/**
	 * Sends a mail message.
	 *
	 * @return Response The response after attempting to send mail.
	 */
	public function send(): Response
	{
		// Placeholder - actual implementation should be provided in a subclass.
		return Response::create();
	}
}