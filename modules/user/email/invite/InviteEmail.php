<?php declare(strict_types=1);
namespace Modules\User\Email\Invite;

use Common\Email\BasicEmail;

/**
 * InviteEmail
 *
 * Sends an email invitation for a user to join the application.
 *
 * @package Modules\User\Email\Invite
 */
class InviteEmail extends BasicEmail
{
	/**
	 * Adds the body to the email.
	 *
	 * @return string
	 */
	protected function addBody(): string
	{
		$props = $this->props;
		$url = env('urls')->prod;
		$siteName = env('siteName');
		$inviter = $props->inviterName ?? $siteName;

		return <<<HTML
<tr>
	<td style="vertical-align:top;" class="sub-container">
		<h1>You're invited to join {$siteName}!</h1>
		<p>{$inviter} has invited you to join {$siteName}. Click the button below to accept the invitation and get started:</p>
		<p><a href="{$url}" class="bttn">Join {$siteName}</a></p>
		<p>If you did not expect this invitation, you can ignore this email.</p>
	</td>
</tr>
{$this->addCompanySignature()}
HTML;
	}
}
