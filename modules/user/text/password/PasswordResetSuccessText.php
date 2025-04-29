<?php declare(strict_types=1);
namespace Modules\User\Text\Password;

use Common\Text\Text;

/**
 * PasswordResetSuccessText
 *
 * Sends a confirmation message that the password has been reset successfully.
 *
 * @package Modules\User\Text\Password
 */
class PasswordResetSuccessText extends Text
{
	/**
	 * This should be overridden to return the message body.
	 *
	 * @return string
	 */
	protected function setupBody(): string
	{
		return <<<EOT
Your password has been reset successfully. You can now sign in with your new password.
If you did not request this change, please contact support immediately.
EOT;
	}
}
