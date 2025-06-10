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
		'data',
		'createdAt',
		'updatedAt'
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
					->table()
					->update('id = ?', 'updated_at = ?')
					->where('id = ?')
					->execute([$newId, $dateTime, $oldId]);
	}

	/**
	 * Retrieves the IDs of expired sessions.
	 *
	 * This method fetches session IDs that have not been updated in the last 6 months.
	 *
	 * @return array
	 */
	public function getExpiredSessions(): array
	{
		$expirationDate = date('Y-m-d H:i:s', strtotime("-6 months"));
		return $this->storage()
					->table()
					->select('id')
					->where('updated_at < ?')
					->fetch([$expirationDate]);
	}

	/**
	 * Retrieves the IDs of empty sessions.
	 *
	 * This method fetches session IDs that have no associated data.
	 *
	 * @return array
	 */
	public function getEmptySessions(): array
	{
		return $this->storage()
					->table()
					->select('id')
					->where('data IS NULL')
					->fetch();
	}
}