<?php declare(strict_types=1);
namespace Modules\Auth\Text\Auth;

use Common\Text\Text;

/**
 * AuthMultiFactorText
 *
 * Sends a message when a multi-factor authentication code is generated.
 *
 * @package Modules\Auth\Text\Auth
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
This is the sign-in auth code {$code}.
EOT;
	}
}