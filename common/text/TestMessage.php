<?php declare(strict_types=1);
namespace Common\Text;

/**
 * TestMessage
 *
 * This is a test message.
 *
 * @package Common\Text
 */
class TestMessage extends Text
{
	/**
     * This should be overriden to return the message body.
     *
     * @abstract
     * @return string
     */
    protected function setupBody(): string
	{
		return <<<EOT
This is a test sms.
EOT;
	}
}