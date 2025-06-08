<?php declare(strict_types=1);
namespace Modules\Auth\Storage;

use Proto\Storage\Storage;

/**
 * LoginAttemptStorage
 *
 * This will handle the login attempts storage.
 *
 * @package Modules\Auth\Storage
 */
class LoginAttemptStorage extends Storage
{
	/**
	 * Count the number of login attempts for a given IP address and username.
	 *
	 * @param string $ipAddress The IP address of the user.
	 * @param string $username The username of the user.
	 * @return int The number of login attempts.
	 */
	public function countAttempts(string $ipAddress, string $username): int
	{
		$dateTime = date('Y-m-d H:i:s');

		$row = $this->table()
			->select([['COUNT(*)'], 'total'])
			->join(function($joins)
			{
				$joins->left('login_attempt_usernames', 'lu')
					->on('a.username_id = la.id')
					->fields(
						'username'
					);
			})
			->where(
				"{$this->alias}.ip_address = ?",
				"lu.username = ?",
				"{$this->alias}.created_at >= DATE_SUB('{$dateTime}', INTERVAL 15 MINUTE)"
			)
			->first([
				$ipAddress,
				$username
			]);

		return $row->total ?? 0;
	}
}