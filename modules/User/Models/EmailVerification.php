<?php declare(strict_types=1);
namespace Modules\User\Models;

use Modules\User\Storage\EmailVerificationStorage;
use Proto\Models\Model;

/**
 * EmailVerification
 *
 * @package Modules\User\Models
 */
class EmailVerification extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'email_verification';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'e';

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
	protected static string $storageType = EmailVerificationStorage::class;
}