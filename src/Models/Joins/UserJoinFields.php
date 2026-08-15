<?php declare(strict_types=1);
namespace Proto\Models\Joins;

/**
 * UserJoinFields
 *
 * Shared user-join field presets so list endpoints do not leak email,
 * mobile, or other private columns when belonging-to a user.
 *
 * @package Proto\Models\Joins
 */
class UserJoinFields
{
	/**
	 * Public profile card (name, avatar, handle, verification).
	 *
	 * @var array<int, string|array{0: string, 1: string}>
	 */
	public const PUBLIC_PROFILE = [
		'firstName',
		'lastName',
		'displayName',
		'image',
		'imageVariants',
		'username',
		'verified',
		'city',
		'state',
	];

	/**
	 * Author byline on posts, comments, and listings.
	 *
	 * @var array<int, string|array{0: string, 1: string}>
	 */
	public const PUBLIC_AUTHOR = [
		'firstName',
		'lastName',
		'displayName',
		'image',
		'imageVariants',
		'username',
		'verified',
	];

	/**
	 * Status aliased so it does not collide with the parent table.
	 *
	 * @var array<int, array{0: string, 1: string}>
	 */
	public const ALIASED_STATUS = [
		['status', 'userStatus'],
	];

	/**
	 * Admin / staff views — still excludes password hashes.
	 *
	 * @var array<int, string|array{0: string, 1: string}>
	 */
	public const ADMIN_PROFILE = [
		'firstName',
		'lastName',
		'displayName',
		'image',
		'username',
		'email',
		'verified',
		['status', 'userStatus'],
	];
}
