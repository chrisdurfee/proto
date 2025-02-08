<?php declare(strict_types=1);
namespace Proto\Html;

use Proto\Utils\Sanitize;

/**
 * Class Template
 *
 * Abstract base class for generating HTML templates.
 *
 * @package Proto\Html
 */
abstract class Template
{
	/**
	 * @var object|null $props Template properties.
	 */
	protected ?object $props = null;

	/**
	 * @var bool $sanitize Determines whether properties should be sanitized.
	 */
	protected bool $sanitize = true;

	/**
	 * Initializes the template with given properties.
	 *
	 * @param object|array|null $props Properties for the template.
	 */
	public function __construct(object|array|null $props = null)
	{
		$this->setupProps($props);
	}

	/**
	 * Sets up template properties.
	 *
	 * @param object|array|null $props Properties to be assigned.
	 */
	protected function setupProps(object|array|null $props = null): void
	{
		if ($props === null)
		{
			$this->props = new \stdClass();
			return;
		}

		$this->props = is_array($props) ? (object)$props : $props;

		// Sanitize the properties if enabled
		if ($this->sanitize)
		{
			$this->props = Sanitize::cleanHtmlEntities($this->props);
		}
	}

	/**
	 * Retrieves a property by its name.
	 *
	 * @param string $propName The name of the property.
	 * @return mixed|null Returns the property value or null if not found.
	 */
	protected function get(string $propName): mixed
	{
		return $this->props->{$propName} ?? null;
	}

	/**
	 * Returns the HTML content of the component.
	 *
	 * @return string The HTML body.
	 */
	abstract protected function getBody(): string;

	/**
	 * Renders the template into an HTML string.
	 *
	 * @return string The rendered HTML.
	 */
	public function render(): string
	{
		return $this->getBody();
	}

	/**
	 * Converts the template to a string by rendering it.
	 *
	 * @return string The rendered template.
	 */
	public function __toString(): string
	{
		return $this->render();
	}
}