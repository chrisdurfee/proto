<?php declare(strict_types=1);
namespace Modules\User\Gateway;

use Modules\User\Models\User;
use Modules\User\Services\User\NewUserService;

/**
 * Gateway
 *
 * This will handle the user module gateway.
 *
 * @package Modules\User\Gateway
 */
class Gateway
{
	/**
	 * This will add the user.
	 *
	 * @return bool
	 */
	public function add(object $settings): bool
	{
		$model = new User($settings);
		return $model->add();
	}

	/**
	 * This will add the user.
	 *
	 * @return bool
	 */
	public function update(object $settings): bool
	{
		$model = new User($settings);
		return $model->update();
	}

	/**
	 * This will get the user by ID.
	 *
	 * @param mixed $id
	 * @return User|null
	 */
	public function get(mixed $id): ?User
	{
		return User::get($id);
	}

	/**
	 * This will get the user by credentials.
	 *
	 * @param string $username
	 * @param string $password
	 * @return int
	 */
	public function authenticate(string $username, string $password): int
	{
		return User::authenticate($username, $password);
	}

	/**
	 * This will register a new user.
	 *
	 * @param object $data
	 * @return User|null
	 */
	public function register(object $data): ?User
	{
		$service = new NewUserService();
		return $service->createUser($data);
	}

	/**
	 * This will get the user by email.
	 *
	 * @param string $email
	 * @return User|null
	 */
	public function getByEmail(string $email): ?User
	{
		$model = new User();
		return $model->getByEmail($email);
	}

	/**
	 * This will update the user status.
	 *
	 * @param int $id
	 * @param string $status
	 * @return bool
	 */
	public function updateStatus(int $id, string $status): bool
	{
		$model = new User((object)[
			'id' => $id,
			'status' => $status
		]);
		return $model->updateStatus();
	}

	/**
	 * This will return the push gateway.
	 *
	 * @return PushGateway
	 */
	public function push(): PushGateway
	{
		return new PushGateway();
	}
}