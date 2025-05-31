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
		$model->password = PasswordHelper::saltPassword($model->password);

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
			$data->password = PasswordHelper::saltPassword($data->password);
		}

		return $this->db->update($this->tableName, $data);
	}

	/**
	 * This will update the password for the user.
	 *
	 * @param string $password
	 * @return bool
	 */
	public function updatePassword(string $password): bool
	{
		$id = $this->model->id ?? null;
		if ($id === null)
		{
			return false;
		}

		$password = PasswordHelper::saltPassword($password);

		return $this->db->update($this->tableName, (object)[
			'id' => $id,
			'password' => $password
		]);
	}

	/**
	 * This will check if a username is taken.
	 *
	 * @param string $username
	 * @return bool
	 */
	public function isUsernameTaken(string $username): bool
	{
		if (!$username)
		{
			return true;
		}

		$params = ['username' => $username];

		$rows = $this->select('id', 'password')
			->where(
				'username = ?'
			)
			->fetch($params);

		return (count($rows) > 0);
	}

	/**
	 * This will get a user by email.
	 *
	 * @param string $email
	 * @return object|null
	 */
	public function getByEmail(string $email): ?object
	{
		if (!$email)
		{
			return null;
		}

		$params = ['email' => $email];

		return $this->select()
			->where(
				'email = ?'
			)
			->first($params);
	}

	/**
	 * This will update the username for the user.
	 *
	 * @param int $id
	 * @param string $username
	 * @return int|bool True is successful false on error. -1 if username is taken.
	 */
	protected function updateUsername(int $id, string $username): bool|int
	{
		$taken = $this->isUsernameTaken($username);
		if ($taken === true)
		{
			return -1;
		}

		return $this->db->update($this->tableName, (object)[
			'id' => $id,
			'username' => $username
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

		if (!$row)
		{
			return $userId;
		}

		if (PasswordHelper::verifyPassword($password, $row->password))
		{
			$userId = $row->id;
		}

		return $userId;
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

		if (!$row)
		{
			return $userId;
		}

		if (PasswordHelper::verifyPassword($password, $row->password))
		{
			$userId = $row->id;
		}

		return $userId;
	}

	/**
	 * Retrieve rows by filter, limit, and modifiers.
	 *
	 * @param array|object|null $filter Filter criteria.
	 * @param int|null $offset Offset.
	 * @param int|null $limit Limit count.
	 * @param array|null $modifiers Modifiers.
	 * @return object
	 */
	public function getRows(mixed $filter = null, ?int $offset = null, ?int $limit = null, ?array $modifiers = null): object
	{
		$params = [];
		$where = static::getWhere($params, $filter, $modifiers);
		$sql = $this->select()
			->where(...$where)
			->limit($offset, $limit);

		$params[] = '2';
		$sql->where('ou.organization_id = ?');

		$this->setOrderBy($sql, $modifiers);
		$sql->debug();

		$rows = $sql->fetch($params);
		return (object)[ 'rows' => $rows];
	}
}