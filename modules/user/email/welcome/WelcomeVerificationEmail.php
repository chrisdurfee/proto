<?php declare(strict_types=1);
namespace Modules\User\Email\Welcome;

use Common\Email\BasicEmail;

/**
 * WelcomeVerificationEmail
 *
 * Welcomes new users and asks them to verify their email address.
 *
 * @package Modules\User\Email\Welcome
 */
class WelcomeVerificationEmail extends BasicEmail
{
	/**
	 * Adds the body to the email.
	 *
	 * @return string
	 */
	protected function addBody(): string
	{
		$props = $this->props;
		$url = $props->verifyUrl ?? '#';
		$siteName = env('siteName');

		return <<<HTML
<tr>
		<td style="vertical-align:top;" class="sub-container">
			<h1>Welcome to {$siteName}!</h1>
			<p>Thank you for signing up. Please verify your email address by clicking the link below:</p>
			<p><a href="{$url}" class="bttn">Verify Email</a></p>
			<p>If you did not create this account, no further action is required.</p>
		</td>
	</tr>
{$this->addCompanySignature()}
HTML;
	}
}
