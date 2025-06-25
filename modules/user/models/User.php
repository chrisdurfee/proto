<?php declare(strict_types=1);
namespace Modules\User\Models;

use Proto\Models\Model;
use Proto\Models\Relations;
use Modules\User\Storage\UserStorage;

/**
 * User
 *
 * This is the model class for table "users".
 *
 * @package Modules\User\Models
 */
class User extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'users';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'u';

	/**
	 * @var array $fields
	 */
	/**
	 * @var array<string> $fields
	 */
	protected static array $fields = [
		// Identity & login
		'id',
		'uuid',
		'username',
		'password',
		'email',
		'emailVerifiedAt',
		'multiFactorEnabled',
		'lastPasswordChangeAt',

		// Profile
		'firstName',
		'lastName',
		'displayName',
		'image',
		'coverImageUrl',
		'bio',
		'dob',
		'gender',

		// Contact & locale
		'timezone',
		'language',
		'currency',
		'country',

		// Preferences & flags
		'status',
		'enabled',
		'emailVerifiedAt',
		'marketingOptIn',
		'acceptedTermsAt',
		'trialMode',
		'trialDaysLeft',

		// Session & activity
		'lastLoginAt',

		// Audit & soft‐delete
		'createdAt',
		'createdBy',
		'updatedAt',
		'updatedBy',
		'deletedAt',

		// social
		'followerCount'
	];

	/**
	 * Fields to exclude when exporting.
	 *
	 * @var array
	 */
	protected static array $fieldsBlacklist = [
		'password'
	];

	/**
	 * Define joins for the User model.
	 *
	 * @param object $builder The query builder object
	 * @return void
	 */
	protected static function joins(object $builder): void
	{
		/**
		 * This will join the user roles and permissions.
		 */
		$builder
			->belongsToMany(Role::class, ['id', 'name', 'slug'], pivotFields: ['organizationId'])
			->belongsToMany(Permission::class, ['id', 'name', 'slug', 'module']);

		$builder
			->belongsToMany(Organization::class, ['id', 'name']);

		$builder
			->one(NotificationPreference::class, fields: [
				[['IF(allow_email = 0, 0, 1)'], 'allowEmail'],
				[['IF(allow_sms = 0, 0, 1)'], 'allowSms'],
				[['IF(allow_push = 0, 0, 1)'], 'allowPush']
			]);
	}

	/**
	 * This will get the user addresses.
	 *
	 * @return Relations\HasMany
	 */
	public function addresses(): Relations\HasMany
	{
		return $this->hasMany(UserAddress::class);
	}

	/**
	 * @var string $storageType
	 */
	protected static string $storageType = UserStorage::class;
}