<?php declare(strict_types=1);
namespace Common\Text\Auth;

use Common\Text\Text;

/**
 * AuthNewConnectionText
 *
 * This is a new connection message.
 *
 * @package Common\Text\Auth
 */
class AuthNewConnectionText extends Text
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
There has been a new multi-factor authorized connection added to your account.
EOT;
	}
}