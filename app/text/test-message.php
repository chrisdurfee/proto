<?php declare(strict_types=1);
namespace App\Text;

/**
 * TestMessage
 *
 * This is a test message.
 *
 * @package App\Text
 */
class TestMessage extends Text
{
	/**
     * This should be overriden to return the message body.
     *
     * @abstract
     * @return string
     */
    protected function setupBody()
	{
		return <<<EOT
This is a test sms.
EOT;
	}
}