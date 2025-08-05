<?php declare(strict_types=1);
namespace Proto\Html\Atoms;

/**
 * Class Script
 *
 * Represents a `<script>` element for including JavaScript files.
 *
 * @package Proto\Html\Atoms
 */
class Script extends Atom
{
	/**
	 * @param string $src The URL of the JavaScript file.
	 */
	public function __construct(protected string $src)
	{
		parent::__construct();
	}

	/**
	 * Generates the `<script>` element.
	 *
	 * @return string The rendered HTML.
	 */
	protected function getBody(): string
	{
		return <<<HTML
		<script src="{$this->src}"></script>
HTML;
	}
}