<?php declare(strict_types=1);
namespace Proto\Html\Atoms;

/**
 * Class Style
 *
 * Represents a `<link>` element for including external stylesheets.
 *
 * @package Proto\Html\Atoms
 */
class Style extends Atom
{
	/**
	 * Initializes the style atom.
	 *
	 * @param string $href The URL of the stylesheet.
	 */
	public function __construct(protected string $href)
	{
		parent::__construct();
	}

	/**
	 * Generates the `<link>` element.
	 *
	 * @return string The rendered HTML.
	 */
	protected function getBody(): string
	{
		return <<<HTML
		<link href="{$this->href}" rel="stylesheet" type="text/css">
HTML;
	}
}
