<?php declare(strict_types=1);
namespace Modules\Auth\Email\Password;

use Common\Email\BasicEmail;

/**
 * PasswordResetRequestEmail
 *
 * Sends a password reset link to the user.
 *
 * @package Modules\Auth\Email\Password
 */
class PasswordResetRequestEmail extends BasicEmail
{
	/**
	 * Adds the body to the email.
	 *
	 * @return string
	 */
	protected function addBody(): string
	{
		$props = $this->props;
		$user = $props->username ?? '';
		$url = $props->resetUrl ?? '#';

		return <<<HTML
<tr>
	<td style="vertical-align:top;" class="sub-container">
		<h1>Password Reset Request</h1>
		<p>Hi {$user},</p>
		<p>We received a request to reset your password. Click the link below to set a new password:</p>
		<p><a href="{$url}" class="bttn">Reset Password</a></p>
		<p>If you didn't request a password reset, you can safely ignore this email.</p>
	</td>
</tr>
{$this->addCompanySignature()}
HTML;
	}
}