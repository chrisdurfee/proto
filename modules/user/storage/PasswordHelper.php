<?php declare(strict_types=1);
namespace Modules\User\Storage;

/**
 * UserStorage
 *
 * This is the storage class for the User model.
 *
 * @package Modules\User\Storage
 */
class PasswordHelper
{
	/**
	 * This will salt a password.
	 *
	 * @param string $password
	 * @return string
	 */
	public static function saltPassword(string $password): string
	{
		$options = [
			'cost' => 10
		];

		return password_hash($password, PASSWORD_BCRYPT, $options);
	}

	/**
	 * This will verify the password.
	 *
	 * @param string $password
	 * @param string $hash
	 * @return bool
	 */
	public static function verifyPassword(string $password, string $hash): bool
	{
		return password_verify($password, $hash);
	}
}