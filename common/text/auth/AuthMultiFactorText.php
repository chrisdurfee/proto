<?php declare(strict_types=1);
namespace Common\Text\Auth;

use Common\Text\Text;

/**
 * AuthMultiFactorText
 *
 * This is a multi factor auth message.
 *
 * @package Common\Text\Auth
 */
class AuthMultiFactorText extends Text
{
	/**
     * This should be overriden to return the message body.
     *
     * @abstract
     * @return string
     */
    protected function setupBody(): string
	{
		$code = $this->get('code');

		return <<<EOT
This is the auth code {$code}.
EOT;
	}
}