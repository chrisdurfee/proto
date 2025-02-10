<?php declare(strict_types=1);
namespace Proto\Controllers\Sms;

use Proto\Controllers\Controller;

/**
 * SmsController
 *
 * Base SMS controller class that defines the send method
 * that SMS controllers must implement.
 *
 * @package Proto\Controllers\Sms
 */
abstract class SmsController extends Controller
{
	/**
	 * Sends a message.
	 *
	 * @param string $session The session identifier.
	 * @param string $to The recipient.
	 * @param string $message The message content.
	 * @return object|bool
	 */
	abstract public function send(string $session, string $to, string $message): object|bool;
}