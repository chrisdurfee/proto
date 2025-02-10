<?php declare(strict_types=1);
namespace Proto\Controllers\Sms;

use Proto\Controllers\Controller;

/**
 * SmsController
 *
 * This is the base sms controller class that contains
 * the send method sms controllers must follow.
 *
 * @package Proto\Controllers\Sms
 */
abstract class SmsController extends Controller
{
    /**
	 * This will send a message.
	 *
	 * @param string $session
	 * @param string $to
	 * @param string $message
	 * @return object|bool
	 */
	abstract public function send(
		string $session,
		string $to,
		string $message
	): object|bool;
}