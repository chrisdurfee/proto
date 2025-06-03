<?php declare(strict_types=1);
namespace Modules\Auth\Email\Password;

use Common\Email\BasicEmail;

/**
 * PasswordResetSuccessEmail
 *
 * Notifies the user that their password has been reset successfully.
 *
 * @package Modules\Auth\Email\Password
 */
class PasswordResetSuccessEmail extends BasicEmail
{
	/**
	 * Adds the body to the email.
	 *
	 * @return string
	 */
	protected function addBody(): string
	{
		$props = $this->props;

		return <<<HTML
<tr>
	<td style="vertical-align:top;" class="sub-container">
		<h1>Password Reset Successful</h1>
		<p>Your password has been reset successfully. You can now sign in with your new password.</p>
		<p>If you did not request this change, please contact support immediately.</p>
	</td>
</tr>
{$this->addCompanySignature()}
HTML;
	}
}