<?php
namespace App\Push;

class PushTest extends Push
{
	/**
     * This should be overriden to return the message body.
     *
     * @abstract
     * @return string|array
     */
    protected function setupBody()
	{
		$ticket = $this->get('ticket');

		return [
			'title' => "Test push message from DentalQore.",
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