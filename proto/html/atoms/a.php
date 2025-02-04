<?php declare(strict_types=1);
namespace Proto\Html\Atoms;

class A extends Atom
{
	/**
	 * This will get the text.
	 *
	 * @return string
	 */
	protected function getText(): string
	{
		return $this->get('text') ?? '';
	}

	/**
	 * This will get the url.
	 *
	 * @return string
	 */
	protected function getUrl(): string
	{
		return $this->get('url') ?? '';
	}

	/**
	 * This will get the className.
	 *
	 * @return string
	 */
	protected function getClassName(): string
	{
		return $this->get('className') ?? '';
	}

	protected function getBody()
	{
		$className = $this->getClassName();

		return <<<EOT
		<a href="{$this->getUrl()}" class="{$className}">
			{$this->getText()}
		</a>
EOT;
	}
}
