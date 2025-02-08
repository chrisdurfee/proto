<?php declare(strict_types=1);
namespace Proto\Html\Atoms;

/**
 * Class Img
 *
 * Represents an `<img>` element.
 *
 * @package Proto\Html\Atoms
 */
class Img extends Atom
{
	/**
	 * Retrieves the `alt` attribute for the image.
	 *
	 * @return string
	 */
	protected function getAlt(): string
	{
		return $this->get('alt') ?? '';
	}

	/**
	 * Retrieves the `src` attribute for the image.
	 *
	 * @return string
	 */
	protected function getSrc(): string
	{
		return $this->get('src') ?? '';
	}

	/**
	 * Generates the `<img>` element.
	 *
	 * @return string The rendered HTML.
	 */
	protected function getBody(): string
	{
		$src = $this->getSrc();
		$alt = $this->getAlt();
		$className = $this->get('className') ?? '';

		return <<<HTML
		<img src="{$src}" alt="{$alt}" class="{$className}" />
HTML;
	}
}