<?php declare(strict_types=1);
namespace Proto\Html\Atoms;

/**
 * Class InlineStyle
 *
 * Represents an inline `<style>` block.
 *
 * @package Proto\Html\Atoms
 */
class InlineStyle extends Atom
{
	/**
	 * Initializes an inline style element.
	 *
	 * @param string $content The CSS content inside the `<style>` tag.
	 */
	public function __construct(protected string $content)
	{
		parent::__construct();
	}

	/**
	 * Generates the inline `<style>` element.
	 *
	 * @return string The rendered HTML.
	 */
	protected function getBody(): string
	{
		return <<<HTML
		<style type="text/css">
			{$this->content}
		</style>
HTML;
	}
}