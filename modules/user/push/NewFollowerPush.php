<?php declare(strict_types=1);
namespace Modules\User\Push;

use Common\Push\Push;

/**
 * Class NewFollowerPush
 *
 * This class is used for sending push notifications when a user gains a new follower.
 *
 * @package Modules\User\Push
 */
class NewFollowerPush extends Push
{
	/**
	 * This should be overridden to return the message body.
	 *
	 * @abstract
	 * @return string|array
	 */
	protected function setupBody(): string|array
	{
		return [
			'title' => "You have a new follower!",
			'message' => $this->getMessage()
		];
	}

	/**
	 * This will get the message.
	 *
	 * @return string
	 */
	protected function getMessage(): string
	{
		$user = $this->get('user');
		$follower = $this->get('follower');

		return <<<EOT
{$follower->firstName} is now following you.
EOT;
	}
}