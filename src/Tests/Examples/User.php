<?php declare(strict_types=1);
namespace Proto\Tests\Examples;

use Proto\Models\Model;
use Proto\Models\HasFactory;
use Proto\Models\Joins\JoinBuilder;

/**
 * User Model (Example)
 *
 * Example model demonstrating factory usage.
 * This is for testing/documentation purposes only.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $status
 * @property string $role
 * @property string $created_at
 * @property string $updated_at
 *
 * @package Proto\Tests\Examples
 */
class User extends Model
{
	use HasFactory;

	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'users';

	/**
	 * @var string $idKeyName
	 */
	protected static string $idKeyName = 'id';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'name',
		'email',
		'password',
		'status',
		'role',
		'createdAt',
		'updatedAt'
	];

	/**
	 * @var array $fieldsBlacklist Fields to exclude from export
	 */
	protected static array $fieldsBlacklist = [
		'password'
	];

	/**
	 * Define model joins.
	 *
	 * @param JoinBuilder $builder
	 * @return void
	 */
	public static function joins(JoinBuilder $builder): void
	{
		// Example: $builder->one(Profile::class);
		// Example: $builder->many(Post::class);
	}
}
