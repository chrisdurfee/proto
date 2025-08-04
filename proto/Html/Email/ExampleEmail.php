<?php declare(strict_types=1);
namespace Proto\Html\Email;

/**
 * Class ExampleEmail
 *
 * An example email template with a header, body, and footer.
 *
 * @package Proto\Html\Email
 */
class ExampleEmail extends Email
{
	/**
	 * Generates the email header content.
	 *
	 * @return string
	 */
	protected function addHeader(): string
	{
		return <<<HTML
		<table role="presentation" width="100%" cellspacing="0" cellpadding="0">
			<tr>
				<td align="center">
					<strong>Header Content</strong>
				</td>
			</tr>
		</table>
HTML;
	}

	/**
	 * Generates the email body content.
	 *
	 * @return string
	 */
	protected function addBody(): string
	{
		return <<<HTML
		<table role="presentation" width="100%" cellspacing="0" cellpadding="0">
			<tr>
				<td align="left">
					<p>Body Content Goes Here</p>
				</td>
			</tr>
		</table>
HTML;
	}

	/**
	 * Generates the email footer content.
	 *
	 * @return string
	 */
	protected function addFooter(): string
	{
		return <<<HTML
		<table role="presentation" width="100%" cellspacing="0" cellpadding="0">
			<tr>
				<td align="center">
					<small>Footer Content - &copy; 2024 Company Name</small>
				</td>
			</tr>
		</table>
HTML;
	}
}