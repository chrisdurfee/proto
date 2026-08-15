<?php declare(strict_types=1);
namespace Proto\Models\Scopes;

use Proto\Storage\Filter;
use Proto\Utils\Sanitize;
use Proto\Utils\Strings;

/**
 * VisibleScope
 *
 * Restricts lists to rows the actor owns, or rows that are public
 * and published. Admins with the configured permission skip the gate.
 *
 * @package Proto\Models\Scopes
 */
class VisibleScope implements Scope
{
	/**
	 * @param string $userField Owner column on the model.
	 * @param string $privacyField Visibility column.
	 * @param string $publicValue Value treated as publicly listable.
	 * @param string $statusField Status column.
	 * @param string $publishedValue Value treated as published.
	 * @param string|null $alias Table alias (required when the model joins).
	 * @param string|null $adminPermission Permission that bypasses the gate.
	 */
	public function __construct(
		protected string $userField = 'userId',
		protected string $privacyField = 'privacy',
		protected string $publicValue = 'public',
		protected string $statusField = 'status',
		protected string $publishedValue = 'published',
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
		$privacyCol = $this->qualify($this->privacyField);
		$statusCol = $this->qualify($this->statusField);

		$conditions[] = [
			"({$userCol} = ? OR ({$privacyCol} = ? AND {$statusCol} = ?))",
			[$userId, $this->publicValue, $this->publishedValue]
		];

		return $conditions;
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
