<?php declare(strict_types=1);
namespace Proto\Html\Atoms\Headers;

/**
 * Class H3
 *
 * Represents an `<h3>` element.
 *
 * @package Proto\Html\Atoms\Headers
 */
class H3 extends Header
{
	/**
	 * Generates the `<h3>` element.
	 *
	 * @return string The rendered HTML.
	 */
	protected function getBody(): string
	{
		$title = $this->getTitle();
		$className = $this->get('className') ?? $this->className;

		return <<<HTML
		<h3 class="{$className}">{$title}</h3>
HTML;
	}
}