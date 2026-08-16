<?php declare(strict_types=1);
namespace Proto\Models\Scopes;

/**
 * VisibleScope
 *
 * Restricts lists to rows the actor owns, or rows that are public
 * and published. Admins with the configured permission skip the gate.
 *
 * This is a thin preset of {@see OwnershipVisibilityScope}
 * (`privacy = publicValue AND status = publishedValue`); public
 * behavior and SQL/params shape are unchanged from before
 * `OwnershipVisibilityScope` existed.
 *
 * @package Proto\Models\Scopes
 */
class VisibleScope extends OwnershipVisibilityScope
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
		string $userField = 'userId',
		protected string $privacyField = 'privacy',
		protected string $publicValue = 'public',
		protected string $statusField = 'status',
		protected string $publishedValue = 'published',
		?string $alias = null,
		?string $adminPermission = 'user.edit'
	)
	{
		parent::__construct(
			userField: $userField,
			visibilityConditions: [
				[$privacyField, '=', $publicValue],
				[$statusField, '=', $publishedValue]
			],
			alias: $alias,
			adminPermission: $adminPermission
		);
	}
}
