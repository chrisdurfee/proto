<?php declare(strict_types=1);
namespace Proto\Database\QueryBuilder;

use Proto\Tests\Debug;

/**
 * Template
 *
 * This will be the base class for all of the query
 * templates.
 *
 * @package Proto\Database\QueryBuilder
 * @abstract
 */
abstract class Template
{
    /**
     * This will render the query.
     *
     * @return string
     */
    protected function render(): string
    {
        return '';
    }

    /**
     * This will return the query as a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return trim($this->render());
    }

    /**
     * This will render the query to screen.
     *
     * @return self
     */
    public function debug(): self
    {
        Debug::render((string)$this);
        return $this;
    }
}