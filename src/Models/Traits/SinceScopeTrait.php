<?php declare(strict_types=1);
namespace Proto\Models\Traits;

use Proto\Storage\Filter;

/**
 * SinceScopeTrait
 *
 * Parameterized "changed since $timestamp" filters for sync endpoints.
 * Never interpolate the client value into SQL — Filter::since() binds it.
 *
 * Usage:
 * ```php
 * $filter[] = static::since($lastSync);
 * $filter[] = static::since($lastSync, ['updatedAt', 'createdAt']);
 * ```
 *
 * @package Proto\Models\Traits
 */
trait SinceScopeTrait
{
	/**
	 * Build a parameterized since-condition using this model's alias.
	 *
	 * @param string $timestamp Client datetime (MySQL/ISO).
	 * @param string|array<int, string> $columns
	 * @return array{0: string, 1: array<int, string>}
	 */
	public static function since(string $timestamp, string|array $columns = 'updatedAt'): array
	{
		$alias = static::alias() ?? '';
		return Filter::since($alias, $timestamp, $columns);
	}
}
