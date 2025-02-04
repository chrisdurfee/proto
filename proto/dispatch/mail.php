<?php declare(strict_types=1);
namespace Proto\Dispatch;

/**
 * Mail
 *
 * This is a placeholder to support sending mail.
 *
 * @package Proto\Dispatch
 */
class Mail extends Dispatch
{
	public function send(): Response
	{
		return Response::create();
	}
}