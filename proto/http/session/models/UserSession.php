<?php declare(strict_types=1);
namespace Proto\Http\Session\Models;

use Proto\Models\Model;

/**
 * UserSession
 *
 * This will create a user session model.
 *
 * @package Proto\Http\Session\Models
 */
class UserSession extends Model
{
	/**
	 * @var string|null $tableName
	 */
	protected static ?string $tableName = 'user_sessions';

	/**
	 * @var string|null $alias
	 */
	protected static ?string $alias = 'us';

	/**
	 * @var array $fields
	 */
	protected static array $fields = [
		'id',
		'access',
		'data'
	];

	/**
	 * Swap out the primary‐key value.
	 *
	 * @param string $oldId
	 * @param mixed $newId
	 * @return bool
	 */
	public function refreshId(string $oldId, $newId): bool
	{
		$dateTime = date('Y-m-d H:i:s');
		return $this->storage()
					->table('user_sessions', 'us')
					->update('id = ?', 'updated_at = ?')
					->where('id = ?')
					->execute([$newId, $dateTime, $oldId]);
	}
}