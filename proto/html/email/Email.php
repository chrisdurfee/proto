<?php declare(strict_types=1);
namespace Proto\Html\Email;

use Proto\Html\Template;
use Proto\Html\Atoms\InlineStyle;

/**
 * Class Email
 *
 * Base class for generating email templates.
 *
 * @package Proto\Html\Email
 * @abstract
 */
abstract class Email extends Template
{
	/**
	 * @var string $styles Inline styles for the email.
	 */
	protected string $styles = '';

	/**
	 * Returns the title text for the email.
	 *
	 * @return string
	 */
	protected function getTitle(): string
	{
		$siteName = env('siteName');
		return $this->get('title') ?? $siteName;
	}

	/**
	 * Generates the email's main content structure.
	 *
	 * @return string
	 */
	protected function getContent(): string
	{
		return <<<HTML
		<table class="main-container" cellpadding="0" cellspacing="0" align="center">
			<tbody>
				<tr>
					<td>{$this->addHeader()}</td>
				</tr>
				<tr>
					<td>{$this->addBody()}</td>
				</tr>
				<tr>
					<td>{$this->addFooter()}</td>
				</tr>
			</tbody>
		</table>
HTML;
	}

	/**
	 * Generates the email header content.
	 *
	 * @return string
	 */
	protected function addHeader(): string
	{
		return '';
	}

	/**
	 * Generates the email body content.
	 *
	 * @return string
	 */
	protected function addBody(): string
	{
		return '';
	}

	/**
	 * Generates the email footer content.
	 *
	 * @return string
	 */
	protected function addFooter(): string
	{
		return '';
	}

	/**
	 * Returns the inline styles for the email.
	 *
	 * @return string
	 */
	protected function getStyles(): string
	{
		return !empty($this->styles) ? (new InlineStyle($this->styles))->__toString() : '';
	}

	/**
	 * Generates the full HTML email.
	 *
	 * @return string
	 */
	protected function getBody(): string
	{
		return <<<HTML
		<!doctype html>
		<html>
			<head>
				<meta charset="utf-8">
				<meta name="viewport" content="width=697">
				<meta name="x-apple-disable-message-reformatting">
				<title>{$this->getTitle()}</title>
				{$this->getStyles()}
			</head>
			<body>
				{$this->getContent()}
			</body>
		</html>
HTML;
	}
}