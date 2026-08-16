<?php declare(strict_types=1);
namespace Proto\Models\Scopes;

use Proto\Storage\Filter;
use Proto\Utils\Sanitize;
use Proto\Utils\Strings;

/**
 * OwnershipVisibilityScope
 *
 * Restricts lists to rows the actor owns, OR rows that satisfy every
 * AND'd visibility condition (e.g. `enabled = 1 AND moderationStatus =
 * 'approved' AND hiddenAt IS NULL`). Admins with the configured
 * permission skip the gate.
 *
 * Generalizes the owner-or-public-published shape hardcoded in
 * {@see VisibleScope} so controllers with a different, arbitrary AND
 * of visibility conditions (e.g. `enabled=1 AND moderationStatus=
 * 'approved' AND hiddenAt IS NULL`) can use a framework scope instead
 * of hand-rolling the SQL. `VisibleScope` is now a thin preset of this
 * class (`privacy=public AND status=published`); its public behavior
 * and tests are unchanged.
 *
 * @package Proto\Models\Scopes
 */
class OwnershipVisibilityScope implements Scope
{
	/**
	 * Operators accepted in a visibility condition tuple.
	 *
	 * @var array<int, string>
	 */
	protected const ALLOWED_OPERATORS = [
		'=', '!=', '<>', '<', '>', '<=', '>=',
		'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'IS', 'IS NOT'
	];

	/**
	 * @param string $userField Owner column on the model.
	 * @param array<int, array{0: string, 1: string, 2: mixed}> $visibilityConditions
	 *   AND'd visibility conditions, each a `[column, operator, value]`
	 *   tuple, e.g. `['enabled', '=', 1]`, `['hiddenAt', 'IS', null]`,
	 *   `['category', 'IN', ['a', 'b']]`. An empty list makes the
	 *   non-owner branch always match (owner OR anyone).
	 * @param string|null $alias Table alias (required when the model joins).
	 * @param string|null $adminPermission Permission that bypasses the gate.
	 */
	public function __construct(
		protected string $userField = 'userId',
		protected array $visibilityConditions = [],
		protected ?string $alias = null,
		protected ?string $adminPermission = 'user.edit'
	)
	{
	}

	/**
	 * @inheritDoc
	 */
	public function apply(mixed $filter, ?object $actor = null): mixed
	{
		if ($this->actorIsAdmin($actor))
		{
			return $filter;
		}

		$conditions = Filter::get($filter);
		$userId = (int)($actor->id ?? 0);
		$userCol = $this->qualify($this->userField);

		[$visibility, $visibilityParams] = $this->buildVisibilityClause();

		$conditions[] = [
			"({$userCol} = ? OR ({$visibility}))",
			[$userId, ...$visibilityParams]
		];

		return $conditions;
	}

	/**
	 * Builds the AND'd visibility clause and its flat, ordered params.
	 *
	 * @return array{0: string, 1: array<int, mixed>}
	 */
	protected function buildVisibilityClause(): array
	{
		$clauses = [];
		$params = [];

		foreach ($this->visibilityConditions as $condition)
		{
			[$sql, $conditionParams] = $this->buildCondition($condition);
			if ($sql === null)
			{
				continue;
			}

			$clauses[] = $sql;
			foreach ($conditionParams as $param)
			{
				$params[] = $param;
			}
		}

		if (empty($clauses))
		{
			return ['1 = 1', []];
		}

		return [implode(' AND ', $clauses), $params];
	}

	/**
	 * Builds a single parameterized condition clause.
	 *
	 * Supports `IS` / `IS NOT` (or a null value with any operator) for
	 * `IS [NOT] NULL`, `IN` / `NOT IN` with an array value for a
	 * parenthesized, parameterized list, and otherwise a plain
	 * `column operator ?` clause.
	 *
	 * @param mixed $condition Expected `[column, operator, value]`.
	 * @return array{0: string|null, 1: array<int, mixed>} Null SQL means "skip" (malformed tuple).
	 */
	protected function buildCondition(mixed $condition): array
	{
		if (!is_array($condition) || !isset($condition[0], $condition[1]) || !array_key_exists(2, $condition))
		{
			return [null, []];
		}

		$column = $condition[0];
		$operator = $condition[1];
		$value = $condition[2];

		if (!is_string($column) || $column === '' || !is_string($operator))
		{
			return [null, []];
		}

		$operator = strtoupper(trim($operator));
		if (!in_array($operator, self::ALLOWED_OPERATORS, true))
		{
			return [null, []];
		}

		$col = $this->qualify($column);

		if ($value === null)
		{
			$isNegated = in_array($operator, ['!=', '<>', 'IS NOT'], true);
			return [$col . ($isNegated ? ' IS NOT NULL' : ' IS NULL'), []];
		}

		if (in_array($operator, ['IN', 'NOT IN'], true))
		{
			$values = is_array($value) ? array_values($value) : [$value];
			if (empty($values))
			{
				return [($operator === 'IN') ? '1 = 0' : '1 = 1', []];
			}

			$placeholders = implode(', ', array_fill(0, count($values), '?'));
			return ["{$col} {$operator} ({$placeholders})", $values];
		}

		// A non-null value with IS/IS NOT is not meaningful for a bound
		// parameter; fall back to the equivalent equality operator.
		if ($operator === 'IS' || $operator === 'IS NOT')
		{
			$operator = ($operator === 'IS') ? '=' : '!=';
		}

		return ["{$col} {$operator} ?", [$value]];
	}

	/**
	 * @param object|null $actor
	 * @return bool
	 */
	protected function actorIsAdmin(?object $actor): bool
	{
		if ($this->adminPermission === null || $actor === null || !function_exists('auth'))
		{
			return false;
		}

		$permissionGate = auth()->permission ?? null;
		if ($permissionGate === null || !method_exists($permissionGate, 'hasPermission'))
		{
			return false;
		}

		return (bool)$permissionGate->hasPermission($this->adminPermission);
	}

	/**
	 * @param string $field
	 * @return string
	 */
	protected function qualify(string $field): string
	{
		$column = Sanitize::cleanColumn(Strings::snakeCase($field));
		if ($this->alias === null || $this->alias === '')
		{
			return $column;
		}

		return Sanitize::cleanColumn($this->alias) . '.' . $column;
	}
}
