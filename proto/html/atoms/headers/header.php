<?php declare(strict_types=1);
namespace Proto\Html\Atoms\Headers;

use Proto\Html\Atoms\Atom;

/**
 * header
 */
class Header extends Atom
{
	/**
	 * This will get the title text.
	 *
	 * @return string
	 */
	protected function getTitle(): string
	{
		return $this->get('title') ?? '';
	}

	/**
	 *
	 * @var string
	 */
	protected $className = 'title';

	/**
	 * This will get the header className.
	 *
	 * @return string
	 */
	protected function getClassName(): string
	{
		return $this->get('className') ?? $this->className;
	}

	/**
	 *
	 * @return string
	 */
	protected function getBody()
	{
		$className = $this->getClassName();

		return <<<EOT
		<header class="{$className}">
			{$this->getTitle()}
		</header>
EOT;
	}
}
