<?php declare(strict_types=1);
namespace Modules\User\Models;

use Modules\User\Storage\PasswordRequestStorage;
use Proto\Models\Model;
use Proto\Models\Relations;

/**
 * PasswordRequest
 *
 * This will set up the password request model.
 *
 * @package Modules\User\Models
 */
class PasswordRequest extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'password_requests';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'pr';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'createdAt',
		'updatedAt',
		'userId',
		'requestId',
		'status'
	];

	/**
	 * @var string $storageType
	 */
	protected static string $storageType = PasswordRequestStorage::class;

	/**
	 * This will get the user.
	 *
	 * @return Relations\BelongsTo
	 */
	public function user(): Relations\BelongsTo
	{
		return $this->belongsTo(User::class);
	}
}