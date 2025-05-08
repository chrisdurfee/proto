<?php declare(strict_types=1);
namespace Common\Email;

use Proto\Html\Email\Email;
use Proto\Config;
use Proto\Utils\Strings;

/**
 * BasicEmail
 *
 * This basic template is used to style the application emails. It should be used
 * for all child emails.
 *
 * @package Common\Email
 */
class BasicEmail extends Email
{
	/**
	 * This will setup the style
	 *
	 * @var string|null
	 */
	protected static $inlineStyle = null;

	/**
	 * @var string|null
	 */
	protected ?string $envUrl = '';

	/**
	 * This is the base url for the file
	 *
	 * @param string
	 */
	protected string $url = '';

	/**
	 * This is the path to the banner image (if any)
	 *
	 * @param string
	 */
	protected string $bannerImg = '';

	/**
	 * This can be used to add a class to the banner image
	 *
	 * @param string
	 */
	protected string $headerClass = '';

	/**
	 * This will setup the template.
	 *
	 * @param object|array|null $props
	 */
	public function __construct($props = null)
	{
		parent::__construct($props);
		$this->getEnvUrl();
	}

	/**
	 * This will get the email style.
	 *
	 * @return string
	 */
	protected function getStyle(): string
	{
		return (static::$inlineStyle ?? (static::$inlineStyle = $this->getFile(__DIR__ . '/css/main.php')));
	}

	/**
	 * This will get the env url.
	 *
	 * @return string
	 */
	protected function getEnvUrl(): string
	{
		return ($this->envUrl ?? ($this->envUrl = ENV_URL));
	}

	/**
	 * This will get the body of the email
	 *
	 * @return string
	 */
	protected function getBody(): string
	{
		$style = $this->getStyle();

		return <<<HTML
	<!doctype html>
	<html>
		<head>
			<meta charset="utf-8">
			<meta name="viewport" content="width=600">
			<meta name="x-apple-disable-message-reformatting">
			<title>{$this->getTitle()}</title>
			{$style}
			{$this->additionalStyle()}
		</head>
		<body>
			<main>
				{$this->getContent()}
			</main>
		</body>
	</html>
HTML;
	}

	/**
	 * This can be overriden to add additional style to the email
	 *
	 * @return string
	 */
	protected function additionalStyle(): string
	{
		return '';
	}

	/**
	 * This will get the email content.
	 *
	 * @return string
	 */
	protected function getContent(): string
	{
		return <<<HTML
	<table class="main-wrapper" cellpadding="0" cellspacing="0" width="100%" bgcolor="#f4f4f5">
		<tr>
			<td align="center">
				<table class="main-container" cellpadding="0" cellspacing="0" width="100%">
					{$this->addHeader()}
					{$this->addBody()}
					{$this->addFooter()}
				</table>
			</td>
		</tr>
	</table>
HTML;
	}

	/**
	 * This will get the header.
	 *
	 * @return string
	 */
	protected function addHeader(): string
	{
		$src = $this->bannerImg ? 'https://' . Config::url() . $this->bannerImg : '';

		return <<<HTML
		<tr>
			<td class="header {$this->headerClass}" align="center" style="padding: 32px 0;">
			<div style="
				width: 32px;
				height: 32px;
				margin: 16px auto;
				background-color: #000000;
				border-radius: 8px;
				display: block;
			"></div>

				<!-- <img src="{$src}" alt="Company Logo" width="48" height="48" style="border-radius: 12px; display: block;"> -->
			</td>
		</tr>
HTML;
	}

	/**
	 * This will add the banner image e.g.
	 * "<img src="{$src}" alt="Banner Image">"
	 *
	 * @return string
	 */
	protected function addBanner(): string
	{
		$baseUrl = Config::url();
		return '<img src="http://' . $baseUrl . $this->bannerImg . '" alt="Banner image" style="margin:0 auto;">';
	}

	/**
	 * This will get the body.
	 *
	 * @return string
	 */
	protected function addBody(): string
	{
		return <<<HTML
HTML;
	}

	/**
	 * This will get the footer.
	 *
	 * @return string
	 */
	protected function addFooter(): string
	{
		$year = date('Y');
		$company = env('siteName');
		$address = env('companyAddress');
		$unsubscribeUrl = env('unsubscribeUrl');

		return <<<HTML
	<tr>
		<td class="footer" align="center" style="padding: 32px 16px; background: #f9fafb;">
			<p style="margin: 0 0 6px; color: #6b7280;">&copy; {$year} {$company}. All rights reserved.</p>
			<p style="margin: 0 0 6px; color: #6b7280;">{$address}</p>
			<p style="margin: 0; color: #6b7280;">
				<a href="{$unsubscribeUrl}" style="color: #3b82f6; text-decoration: none;">Unsubscribe</a>
			</p>
		</td>
	</tr>
HTML;
	}

	/**
	 * This will add a button
	 *
	 * @param string $href
	 * @param string $btnText
	 * @return string
	 */
	protected function addButton(string $href, string $btnText): string
	{
		return <<<HTML
		<table class="button-container" cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td class="center">
					<a class="bttn" href="{$href}" target="_blank">
						<strong>{$btnText}</strong>
					</a>
				</td>
			</tr>
		</table>
HTML;
	}

	/**
	 * this will add the standard company email signature.
	 *
	 * @return string
	 */
	protected function addCompanySignature(): string
	{
		$phone = env('contactPhone');
		$formattedPhone = Strings::formatPhone($phone, 'NANP');
		return <<<HTML
<tr>
	<td class="sub-container">
		<p>Contact us at: <a href="tel:{$formattedPhone}">{$formattedPhone}</a></p>
	</td>
</tr>
{$this->addBottomMargin()}
HTML;
	}

	/**
	 * This will add a bottom margin table for spacing in outlook.
	 *
	 * @return string
	 */
	protected function addBottomMargin(): string
	{
		return <<<HTML
		<table class="bottom-margin" cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td>&nbsp;</td>
			</tr>
		</table>
HTML;
	}

	/**
	 * This will get the contents of a file.
	 *
	 * @param string $path
	 * @return string
	 */
	protected function getFile(string $path): string
	{
		ob_start();
		include $path;
		return ob_get_clean();
	}
}