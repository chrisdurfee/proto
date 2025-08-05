<?php declare(strict_types=1);
namespace Proto\Html\Atoms;

/**
 * Class A
 *
 * Represents an `<a>` (anchor) element.
 *
 * @package Proto\Html\Atoms
 */
class A extends Atom
{
	/**
	 * Retrieves the anchor text.
	 *
	 * @return string
	 */
	protected function getText(): string
	{
		return $this->get('text') ?? '';
	}

	/**
	 * Retrieves the URL for the anchor.
	 *
	 * @return string
	 */
	protected function getUrl(): string
	{
		return $this->get('url') ?? '';
	}

	/**
	 * Generates the `<a>` element.
	 *
	 * @return string The rendered HTML.
	 */
	protected function getBody(): string
	{
		$url = $this->getUrl();
		$text = $this->getText();
		$className = $this->get('className') ?? '';

		return <<<HTML
		<a href="{$url}" class="{$className}">{$text}</a>
HTML;
	}
}