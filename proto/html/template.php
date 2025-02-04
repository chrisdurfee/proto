<?php declare(strict_types=1);
namespace Proto\Html;

use Proto\Utils\Sanitize;

/**
 * Template
 *
 * This will create a base class to extend tempates to generate
 * files by a type.
 *
 * @package Proto\Generators\Templates
 * @abstract
 */
abstract class Template
{
	/**
	 * @var object $props
	 */
	protected $props;

	/**
	 * @var bool $sanitize
	 */
	protected bool $sanitize  = true;

	/**
	 * This will setup the template.
	 *
	 * @param object|array|null $props
	 * @return void
	 */
	public function __construct($props = null)
	{
		$this->setupProps($props);
	}

	/**
	 * This will setup the template props.
	 *
	 * @param object|array|null $props
	 * @return void
	 */
	protected function setupProps($props = null): void
	{
		if (!$props)
		{
			return;
		}

		if (is_array($props))
		{
			$props = (object)$props;
		}

		$this->props = $props;

		// this will sanitize the data to be rendered in the html email.
		//$this->props = ($this->sanitize === true)? Sanitize::cleanHtmlEntities($props) : $props;
	}

	/**
	 * This will get a prop by key.
	 *
	 * @param string $propName
	 * @return mixed
	 */
	protected function get($propName): mixed
	{
		return $this->props->{$propName} ?? null;
	}

	/**
	 * This should be overriden to return the html of the component.
	 * @abstract
	 * @return string
	 */
	abstract protected function getBody();

	/**
	 * This will render the html to the screen.
	 *
	 * @return string
	 */
	public function render(): string
	{
		return $this->getBody();
	}

	/**
	 * This will override the toString to return the
	 * rendered template.
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		return $this->render();
	}
}
