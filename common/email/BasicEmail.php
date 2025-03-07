<?php declare(strict_types=1);
namespace Common\Email;

use Proto\Html\Email\Email;
use Proto\Config;

/**
 * BasicEmail
 *
 * This is the base email class.
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

		return <<<EOT
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
EOT;
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
		return <<<EOT
		<table class="main-container" cellpadding="0" cellspacing="0" align="center" width="100%">
			{$this->addHeader()}
			{$this->addBody()}
			{$this->addFooter()}
		</table>
EOT;
	}

	/**
	 * This will get the header.
	 *
	 * @return string
	 */
	protected function addHeader(): string
	{
		if(empty($this->bannerImg) === true)
		{
			return "";
		}

		return <<<EOT
		<tr>
			<td class="image-container {$this->headerClass}" style="text-align:center">
				{$this->addBanner()}
			</td>
		</tr>
EOT;
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
		return <<<EOT
EOT;
	}

	/**
	 * This will get the footer.
	 *
	 * @return string
	 */
	protected function addFooter(): string
	{
		$baseUrl = Config::url();
		$siteName = env('siteName');

		return <<<EOT
		<table class="footer" cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td style="width: 284px;"></td>
					<td>
						<a href="https://{$baseUrl}" target="_blank">
							<img src="http://{$baseUrl}/app/email/media/logo.png" alt="{$siteName}">
						</a>
					</td>
				<td style="width: 284px;"></td>
			</tr>
		</table>
EOT;
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
		return <<<EOT
		<table class="button-container" cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td class="center">
					<a class="bttn" href="{$href}" target="_blank">
						<strong>{$btnText}</strong>
					</a>
				</td>
			</tr>
		</table>
EOT;
	}

	/**
	 * this will add the standard company email signature.
	 *
	 * @return string
	 */
	protected function addCompanySignature(): string
	{
		return <<<EOT
<tr>
	<td class="sub-container">
		<p></p>
	</td>
</tr>
{$this->addBottomMargin()}
EOT;
	}

	/**
	 * This will add a bottom margin table for spacing in outlook.
	 *
	 * @return string
	 */
	protected function addBottomMargin(): string
	{
		return <<<EOT
		<table class="bottom-margin" cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td>&nbsp;</td>
			</tr>
		</table>
EOT;
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