<?php declare(strict_types=1);
namespace Modules\Developer\Email\Test;

use Common\Email\BasicEmail;

/**
 * TestEmail
 *
 * This is a test email.
 *
 * @package Modules\Developer\Email\Test
 */
class TestEmail extends BasicEmail
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
			<h1>Test Email</h1>
			<p>This is a test email.</p>
		</td>
	</tr>
{$this->addCompanySignature()}
HTML;
	}
}
