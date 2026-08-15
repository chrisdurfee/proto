<?php declare(strict_types=1);
namespace Proto\Models\Scopes;

/**
 * Scope
 *
 * Appends list-filter conditions that the client cannot override
 * (privacy, audience, published-only, etc.).
 *
 * @package Proto\Models\Scopes
 */
interface Scope
{
	/**
	 * Apply the scope to a filter.
	 *
	 * @param mixed $filter Existing filter (array or object).
	 * @param object|null $actor Session user, if any.
	 * @return mixed
	 */
	public function apply(mixed $filter, ?object $actor = null): mixed;
}
