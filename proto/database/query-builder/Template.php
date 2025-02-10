<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

use Proto\Tests\Debug;

/**
 * Template
 *
 * Base abstract class for building and rendering database query strings.
 *
 * This class provides a template method for generating a query, returning a trimmed version
 * of the query string, and debugging the query output.
 *
 * @package Proto\Database\QueryBuilder
 * @abstract
 */
abstract class Template
{
	/**
	 * Renders the query string.
	 *
	 * Every subclass must implement this method to build its query string.
	 *
	 * @return string The rendered query.
	 */
	abstract protected function render(): string;

	/**
	 * Returns the rendered query as a trimmed string.
	 *
	 * @return string The trimmed query string.
	 */
	public function __toString(): string
	{
		return trim($this->render());
	}

	/**
	 * Renders the query for debugging purposes.
	 *
	 * Outputs the query via the static Debug class and returns the instance for method chaining.
	 *
	 * @return self Returns the current instance.
	 */
	public function debug(): self
	{
		Debug::render((string) $this);
		return $this;
	}
}