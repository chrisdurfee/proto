<?php declare(strict_types=1);
namespace Modules\User\Email\Password;

use Common\Email\BasicEmail;

/**
 * PasswordUpdatedEmail
 *
 * Notifies users when their password has been updated.
 *
 * @package Modules\User\Email\Password
 */
class PasswordUpdatedEmail extends BasicEmail
{
	/**
	 * Adds the body to the email.
	 *
	 * @return string
	 */
	protected function addBody(): string
	{
		$props = $this->props;
		$siteName = env('siteName');

		return <<<HTML
<tr>
		<td style="vertical-align:top;" class="sub-container">
			<h1>Password Updated</h1>
			<p>Your password has been successfully updated. If you did not make this change, please contact support.</p>
		</td>
	</tr>
{$this->addCompanySignature()}
HTML;
	}
}
