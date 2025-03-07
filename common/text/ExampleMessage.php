<?php declare(strict_types=1);
namespace Common\Text;

/**
 * ExampleMessage
 *
 * This is an example message.
 *
 * @package Common\Text
 */
class ExampleMessage extends Text
{
	/**
     * This should be overriden to return the message body.
     *
     * @abstract
     * @return string
     */
    protected function setupBody(): string
	{
		$url = $this->get('url');

		return <<<EOT
Click the url {$url} to view message.
EOT;
	}
}