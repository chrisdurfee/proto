<?php declare(strict_types=1);
namespace Proto\Controllers\Push;

use Proto\Controllers\ModelController;

/**
 * PushController
 *
 * Base class for all push controllers.
 *
 * @package Proto\Controllers\Push
 */
abstract class PushController extends ModelController
{
	/**
	 * Sends a push notification.
	 *
	 * @param object $subscription The subscription object.
	 * @param string $payload The payload to send.
	 * @return bool
	 */
	abstract public function send(object $subscription, string $payload): bool;
}