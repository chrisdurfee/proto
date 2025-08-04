<?php declare(strict_types=1);
namespace Modules\User\Tests\Unit;

use Modules\User\Storage\PasswordHelper;
use Modules\User\Storage\UserStorage;
use Proto\Tests\Test;

/**
 * ConfirmPasswordTest
 *
 * This test class verifies the functionality of confirming a user's password.
 *
 * @package Modules\User\Tests\Unit
 */
class ConfirmPasswordTest extends Test
{
	public function testConfirmPasswordReturnsUserIdWithCorrectPassword(): void
	{
		$storage = new UserStorageStub();
		$storage->addUser(1, 'secret');

		$result = $storage->confirmPassword(1, 'secret');
		$this->assertSame(1, $result);
	}

	public function testConfirmPasswordReturnsMinusOneForInvalidPasswordOrId(): void
	{
		$storage = new UserStorageStub();
		$storage->addUser(1, 'secret');

		$this->assertSame(-1, $storage->confirmPassword(1, 'wrong'));
		$this->assertSame(-1, $storage->confirmPassword(2, 'secret'));
	}
}

/**
 * UserStorageStub
 *
 * A stub for UserStorage to allow testing without a database.
 *
 * @package Modules\User\Tests\Unit
 */
class UserStorageStub extends UserStorage
{
	/**
	 * @var array $users
	 */
	private array $users = [];

	/**
	 * Override the parent constructor to avoid database dependencies.
	 */
	public function __construct()
	{
	}

	/**
	 * Add a user to the storage for testing purposes.
	 *
	 * @param int $id
	 * @param string $password
	 * @param bool $enabled
	 * @return void
	 */
	public function addUser(int $id, string $password, bool $enabled = true): void
	{
		$this->users[$id] = [
			'id' => $id,
			'password' => PasswordHelper::saltPassword($password),
			'enabled' => $enabled ? 1 : 0,
		];
	}

	/**
	 * This will confirm the password for a user.
	 *
	 * @param mixed $userId
	 * @param string $password
	 * @return int|mixed
	 */
	public function confirmPassword(mixed $userId, string $password): int
	{
		$row = $this->users[$userId] ?? null;
		if (!$row || $row['enabled'] !== 1)
		{
			return -1;
		}
		return PasswordHelper::verifyPassword($password, $row['password']) ? $row['id'] : -1;
	}
}
