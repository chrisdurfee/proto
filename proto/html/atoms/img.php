<?php declare(strict_types=1);
namespace Proto\Html\Atoms;

class Img extends Atom
{
	/**
	 * This will get the alt.
	 *
	 * @return string
	 */
	protected function getAlt(): string
	{
		return $this->get('alt') ?? '';
	}

	/**
	 * This will get the src.
	 *
	 * @return string
	 */
	protected function getSrc(): string
	{
		return $this->get('src') ?? '';
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
		<img src="{$this->getSrc()}" class="{$className}" alt="{$this->getAlt()}">
EOT;
	}
}
