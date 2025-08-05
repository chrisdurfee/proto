<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

/**
 * Template
 *
 * This class serves as a base template to generate files by type.
 *
 * @package Proto\Generators\Templates
 * @abstract
 */
abstract class Template
{
	/**
	 * Properties used in the template.
	 *
	 * @var object|null
	 */
	protected ?object $props = null;

	/**
	 * Initializes the template with given properties.
	 *
	 * @param object|array|null $props Template properties.
	 */
	public function __construct(object|array|null $props = null)
	{
		$this->setupProps($props);
	}

	/**
	 * Sets up the template properties.
	 *
	 * @param object|array|null $props Template properties.
	 * @return void
	 */
	protected function setupProps(object|array|null $props = null): void
	{
		if ($props !== null)
		{
			$this->props = is_array($props) ? (object) $props : $props;
		}
	}

	/**
	 * Retrieves a property by its key.
	 *
	 * @param string $propName The property name.
	 * @return mixed The property value or null if not found.
	 */
	protected function get(string $propName): mixed
	{
		return $this->props?->{$propName} ?? null;
	}

	/**
	 * Returns the HTML of the component.
	 *
	 * This method must be overridden by subclasses.
	 *
	 * @abstract
	 * @return string The rendered component body.
	 */
	abstract protected function getBody(): string;

	/**
	 * Renders the HTML to the screen.
	 *
	 * @return string The rendered output.
	 */
	public function render(): string
	{
		return $this->getBody();
	}

	/**
	 * This will remove empty space from the template.
	 *
	 * @param string $string
	 * @return string
	 */
	private function removeEmptySpace(string $string): string
	{
		return preg_replace('/(\r?\n\s*){3,}/', "\n\n", $string);
	}

	/**
	 * Converts the template to a string.
	 *
	 * @return string The rendered output.
	 */
	public function __toString(): string
	{
		$file = $this->render();
		return $this->removeEmptySpace($file);
	}
}