<?php declare(strict_types=1);
namespace Proto\Html\Atoms;

/**
 * Class Pre
 *
 * Represents a `<pre>` element for preformatted text.
 *
 * @package Proto\Html\Atoms
 */
class Pre extends Atom
{
	/**
	 * Generates the `<pre>` element.
	 *
	 * @return string The rendered HTML.
	 */
	protected function getBody(): string
	{
        $content = $this->get('content') ?? '';
		return <<<HTML
		<pre>{$content}</pre>
HTML;
	}
}