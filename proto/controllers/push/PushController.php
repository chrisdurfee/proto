<?php declare(strict_types=1);
namespace Proto\Controllers\Push;

use Proto\Controllers\ModelController;

/**
 * PushController
 *
 * This will be the base class for all push controllers.
 *
 * @package Proto\Controllers\Push
 * @abstract
 */
abstract class PushController extends ModelController
{
	/**
	 * This will send a push notification.
	 *
	 * @param object $subscription
	 * @param string $payload
	 * @return bool
	 */
	abstract public function send(object $subscription, string $payload);
}