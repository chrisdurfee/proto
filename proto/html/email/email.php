<?php declare(strict_types=1);
namespace Proto\Html\Email;

use Proto\Html\Template;
use Proto\Html\Atoms\InlineStyle;

/**
 * Email
 *
 * This will create an email template.
 *
 * @package Proto\Html\Email
 * @abstract
 */
abstract class Email extends Template
{
    /**
     * @var string $styles
     */
    protected string $styles;

    /**
	 * This will get the title text.
	 *
	 * @return string
	 */
	protected function getTitle(): string
	{
		return $this->get('title') ?? 'DentalQore';
    }

    /**
     * This will get the email content.
     *
     * @return string
     */
    protected function getContent()
    {
        return <<<EOT
            <table class="main_container" cellpadding="0" cellspacing="0" align="center">
                <tr>
                    <td>
                        {$this->addHeader()}
                    </td>
                </tr>
                <tr>
                    <td>
                        {$this->addBody()}
                    </td>
                </tr>
                <tr>
                    <td>
                        {$this->addFooter()}
                    </td>
                </tr>
            </table>
EOT;
    }

    /**
     * This will get the header.
     *
     * @return string
     */
    protected function addHeader()
    {
        return <<<EOT

EOT;
    }

    /**
     * This will get the body.
     *
     * @return string
     */
    protected function addBody()
    {
        return <<<EOT

EOT;
    }

    /**
     * This will get the footer.
     *
     * @return string
     */
    protected function addFooter()
    {
        return <<<EOT

EOT;
    }

    /**
     * This will get the email content.
     *
     * @return string
     */
    protected function getStyles()
    {
        $styles = $this->styles ?? '';
        if (!$styles)
        {
            return '';
        }

        return new InlineStyle($styles);
    }

    /**
     * This will get the email body.
     *
     * @return string
     */
    protected function getBody()
	{
        return <<<EOT
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
EOT;
	}
}
