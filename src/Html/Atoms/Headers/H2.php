<?php declare(strict_types=1);
namespace Proto\Html\Atoms\Headers;

/**
 * Class H2
 *
 * Represents an `<h2>` element.
 *
 * @package Proto\Html\Atoms\Headers
 */
class H2 extends Header
{
	/**
	 * Generates the `<h2>` element.
	 *
	 * @return string The rendered HTML.
	 */
	protected function getBody(): string
	{
		$title = $this->getTitle();
		$className = $this->get('className') ?? $this->className;

		return <<<HTML
		<h2 class="{$className}">{$title}</h2>
HTML;
	}
}