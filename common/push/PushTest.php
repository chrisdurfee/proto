<?php declare(strict_types=1);
namespace Common\Push;

/**
 * Class PushTest
 *
 * This class is used for testing push notifications.
 *
 * @package Common\Push
 */
class PushTest extends Push
{
	/**
     * This should be overridden to return the message body.
     *
     * @abstract
     * @return string|array
     */
    protected function setupBody(): string|array
	{
		$ticket = $this->get('ticket');

		return [
			'title' => "Test push message.",
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
		return <<<EOT
Push notification is working.
EOT;
	}
}