<?php declare(strict_types=1);
namespace App\Text\Auth;

use App\Text\Text;

/**
 * AuthNewConnectionText
 *
 * This is a new connection message.
 *
 * @package App\Text\Auth
 */
class AuthNewConnectionText extends Text
{
	/**
     * This should be overriden to return the message body.
     *
     * @abstract
     * @return string
     */
    protected function setupBody()
	{
		$code = $this->get('code');

		return <<<EOT
There has been a new multi-factor authorized connection added to your account..
EOT;
	}
}