<?php declare(strict_types=1);
namespace Modules\User\Auth\Gates;

use Proto\Auth\Gates\Gate;

/**
 * UserGate
 *
 * This will create a user control gate.
 *
 * @package Modules\User\Auth\Gates
 */
class UserGate extends Gate
{
	/**
	 * Checks if the user has the specified user id.
	 *
	 * @param mixed $userId the user id to check.
	 * @return bool True if the user matches the user id, otherwise false.
	 */
	public function isUser(mixed $userId): bool
	{
		$user = $this->get('user');
		if (!$user)
		{
			return false;
		}

		return $user->id === $userId;
	}

	/**
	 * Checks if the user has the specified user id.
	 *
	 * @param string $userId the user id to check.
	 * @return bool True if the user matches the user id, otherwise false.
	 */
	public static function is(mixed $userId): bool
	{
		$instance = new self();
		return $instance->isUser($userId);
	}
}