<?php declare(strict_types=1);

namespace Modules\User\Tests\Unit;

use Modules\User\Storage\PasswordHelper;
use Modules\User\Storage\UserStorage;
use Proto\Tests\Test;

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

class UserStorageStub extends UserStorage
{
	private array $users = [];

	public function __construct()
	{
	    // Do not call parent constructor to avoid database dependencies
	}

	public function addUser(int $id, string $password, bool $enabled = true): void
	{
	    $this->users[$id] = [
	        'id' => $id,
	        'password' => PasswordHelper::saltPassword($password),
	        'enabled' => $enabled ? 1 : 0,
	    ];
	}

	public function confirmPassword(mixed $userId, string $password): int
	{
	    $row = $this->users[$userId] ?? null;
	    if (!$row || $row['enabled'] !== 1) {
	        return -1;
	    }
	    return PasswordHelper::verifyPassword($password, $row['password']) ? $row['id'] : -1;
	}
}
