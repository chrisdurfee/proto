<?php declare(strict_types=1);
namespace Proto\Html\Pages;

use Proto\Html\Template;

/**
 * Class Page
 *
 * Base class for HTML page templates.
 *
 * @package Proto\Html\Pages
 * @abstract
 */
abstract class Page extends Template
{
	/**
	 * Returns the head section of the HTML document.
	 *
	 * @return string The HTML head content.
	 */
	protected function getHead(): string
	{
        $title = $this->get('title') ?? 'Untitled Page';
		return <<<HTML
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>{$title}</title>
		</head>
HTML;
	}

	/**
	 * Renders the full HTML page.
	 *
	 * @return string The complete HTML document.
	 */
	public function render(): string
	{
		return <<<HTML
		<!DOCTYPE html>
		<html lang="en">
			{$this->getHead()}
			<body>
				{$this->getBody()}
			</body>
		</html>
HTML;
	}

	/**
	 * This should return the main body content of the page.
	 *
	 * @return string The HTML body content.
	 */
	abstract protected function getBody(): string;
}