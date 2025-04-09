<?php declare(strict_types=1);
namespace Modules\User\Storage;

use Proto\Storage\Storage;
use Proto\Models\Model;

/**
 * UserStorage
 *
 * This is the storage class for the User model.
 *
 * @package Modules\User\Storage
 */
class UserStorage extends Storage
{
	/**
	 * This will salt a password.
	 *
	 * @param string $password
	 * @return string
	 */
	protected static function saltPassword(string $password): string
	{
		$options = [
			'cost' => 10
		];

		return password_hash($password, PASSWORD_BCRYPT, $options);
	}

	/**
	 * This will add the user with the salted password.
	 *
	 * @return bool
	 */
	public function add(): bool
	{
		/**
		 * @var Model $model
		 */
		$model = $this->model;
		$model->password = self::saltPassword($model->password);

		return parent::add();
	}

	/**
	 * This will update the user. If the password is not set, it will not be updated.
	 *
	 * @return bool
	 */
	public function update(): bool
	{
		$data = $this->getUpdateData();
		if (isset($data->password))
		{
			$data->password = self::saltPassword($data->password);
		}

		return $this->db->update($this->tableName, $data);
	}

	/**
	 * This will update the user password to the table.
	 *
	 * @return bool
	 */
	protected function updatePassword(int $id, string $password): bool
	{
		$password = self::saltPassword($password);

		return $this->db->update($this->tableName, (object)[
			'id' => $id,
			'password' => $password
		]);
	}

	/**
	 * This will authenticate the username and password.
	 *
	 * @param string $username
	 * @param string $password
	 * @return int The user id or -1 if not found.
	 */
	public function authenticate(string $username, string $password): int
	{
		$userId = -1;
		$params = ['username' => $username];

		$row = $this->select('id', 'password')
			->where(
				'username = ?',
				'enabled = 1'
			)
			->first($params);

		if ($row)
		{
			if ($this->verifyPassword($password, $row->password))
			{
				$userId = $row->id;
			}
		}

		return $userId;
	}

	/**
	 * This will verify the password.
	 *
	 * @param string $password
	 * @param string $hash
	 * @return bool
	 */
	private function verifyPassword(string $password, string $hash): bool
	{
		return password_verify($password, $hash);
	}

	/**
	 * This will confirm the password for the user.
	 *
	 * @param mixed $userId
	 * @param string $password
	 * @return int The user id or -1 if not found.
	 */
	public function confirmPassword(mixed $userId, string $password): int
	{
		$userId = -1;
		$params = ['id' => $userId];

		$row = $this->select('id', 'password')
			->where(
				'id = ?',
				'enabled = 1'
			)
			->first($params);

		if ($row)
		{
			if ($this->verifyPassword($password, $row->password))
			{
				$userId = $row->id;
			}
		}

		return $userId;
	}
}