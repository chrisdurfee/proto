<?php declare(strict_types=1);
namespace Modules\User\Text\Password;

use Common\Text\Text;

/**
 * PasswordResetRequestText
 *
 * Sends a password reset code and link via text message.
 *
 * @package Modules\User\Text\Password
 */
class PasswordResetRequestText extends Text
{
	/**
	 * This should be overridden to return the message body.
	 *
	 * @return string
	 */
	protected function setupBody(): string
	{
		$code = $this->get('code');
		$url = $this->get('resetUrl');

		return <<<EOT
Your password reset code is {$code}. Reset your password here: {$url}
EOT;
	}
}