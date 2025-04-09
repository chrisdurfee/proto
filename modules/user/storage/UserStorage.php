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
}