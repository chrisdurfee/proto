<?php declare(strict_types=1);
namespace Proto\Html\Atoms\Headers;

use Proto\Html\Atoms\Atom;

/**
 * Class Header
 *
 * Represents a `<header>` element.
 *
 * @package Proto\Html\Atoms\Headers
 */
class Header extends Atom
{
	/**
	 * @param string $className The CSS class for the header (default: 'title').
	 */
	public function __construct(protected string $className = 'title')
	{
		parent::__construct();
	}

	/**
	 * Retrieves the title text.
	 *
	 * @return string
	 */
	protected function getTitle(): string
	{
		return $this->get('title') ?? '';
	}

	/**
	 * Generates the `<header>` element.
	 *
	 * @return string The rendered HTML.
	 */
	protected function getBody(): string
	{
		$title = $this->getTitle();
		$className = $this->get('className') ?? $this->className;

		return <<<HTML
		<header class="{$className}">{$title}</header>
HTML;
	}
}