<?php declare(strict_types=1);
namespace Modules\User\Text\Auth;

use Common\Text\Text;

/**
 * AuthNewConnectionText
 *
 * Sends a message when a new multi-factor authorized connection is added to the user's account.
 *
 * @package Modules\User\Text\Auth
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
There has been a new multi-factor authorized connection added to your account..
EOT;
	}
}