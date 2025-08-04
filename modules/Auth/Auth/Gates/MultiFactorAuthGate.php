<?php declare(strict_types=1);
namespace Modules\Auth\Auth\Gates;

use Modules\User\Models\User;
use Proto\Auth\Gates\Gate;

/**
 * MultiFactorAuthGate
 *
 * Stores and validates a one‑time authentication code—plus the user
 * and device objects associated with that MFA step—inside the session.
 *
 * @package Modules\Auth\Auth\Gates
 */
class MultiFactorAuthGate extends Gate
{
	/**
	 * Length of the numeric MFA code (e.g. 9 → “123 456 789”).
	 */
	const AUTH_CODE_LENGTH = 9;

	/**
	 * Session key used to persist the MFA code.
	 */
	const AUTH_KEY = 'AUTH_KEY';

	/**
	 * Session key used to persist the user object.
	 */
	const AUTH_USER = 'AUTH_USER';

	/**
	 * Session key used to persist the device object or fingerprint.
	 */
	const AUTH_DEVICE = 'AUTH_DEVICE';

	/**
	 * The maximum number of attempts allowed for MFA code validation.
	 *
	 * @var int
	 */
	const MAX_ATTEMPTS = 10;

	/**
	 * The number of attempts made to validate the MFA code.
	 *
	 * @var string
	 */
	const ATTEMPTS = 'AUTH_ATTEMPTS';

	/**
	 * Generate a random numeric MFA code.
	 *
	 * @return string Nine‑digit code.
	 */
	protected function createCode(): string
	{
		$code = random_int(0, 999999999);
		return str_pad((string) $code, self::AUTH_CODE_LENGTH, '0', STR_PAD_LEFT);
	}

	/**
	 * Generate a new MFA code and store it in the session.
	 *
	 * @return string The freshly generated code.
	 */
	public function setCode(): string
	{
		$code = $this->createCode();
		$this->set(self::AUTH_KEY, $code);
		return $code;
	}

	/**
	 * Persist both user and device references for the current MFA flow.
	 *
	 * @param User $user Authenticated user model.
	 * @param object $device Device/fingerprint model.
	 * @return void
	 */
	public function setResources(User $user, object $device): void
	{
		$this->setUser($user);
		$this->setDevice($device);
	}

	/**
	 * Store the user object in the session.
	 *
	 * @param User $user
	 * @return void
	 */
	public function setUser(User $user): void
	{
		$data = $user->getData();
		$this->set(self::AUTH_USER, $data);
	}

	/**
	 * Retrieve the stored user object.
	 *
	 * @return object|null Returns null if no user has been set.
	 */
	public function getUser(): ?object
	{
		return $this->get(self::AUTH_USER);
	}

	/**
	 * Store the device object in the session.
	 *
	 * @param object $device
	 * @return void
	 */
	public function setDevice(object $device): void
	{
		$this->set(self::AUTH_DEVICE, $device);
	}

	/**
	 * Retrieve the stored device object.
	 *
	 * @return object|null Returns null if no device has been set.
	 */
	public function getDevice(): ?object
	{
		return $this->get(self::AUTH_DEVICE);
	}

	/**
	 * Clear the MFA code, user, and device from the session—typically
	 * called after successful validation.
	 *
	 * @return void
	 */
	protected function resetCode(): void
	{
		$this->set(self::AUTH_KEY, null);
		$this->set(self::AUTH_USER, null);
		$this->set(self::AUTH_DEVICE, null);
	}

	/**
	 * Retrieve the number of attempts made to validate the MFA code.
	 *
	 * @return int The number of attempts.
	 */
	public function getAttempts(): int
	{
		return $this->get(self::ATTEMPTS) ?: 0;
	}

	/**
	 * Set the number of attempts made to validate the MFA code.
	 *
	 * @param int $attempts The number of attempts.
	 * @return void
	 */
	public function setAttempts(int $attempts): void
	{
		$this->set(self::ATTEMPTS, $attempts);
	}

	/**
	 * Compare the provided code against the one stored in the session.
	 * If it matches, the session values are cleared.
	 *
	 * @param string $code Code entered by the user.
	 * @return bool|null True on success, false if the code is invalid, or null if the number of attempts exceeds the maximum allowed.
	 */
	public function validateCode(string $code): ?bool
	{
		$storedCode = $this->get(self::AUTH_KEY);
		if (empty($storedCode))
		{
			return false;
		}

		$valid = ($storedCode === $code);
		if ($valid)
		{
			$this->resetCode();
			$this->setAttempts(0);
			return $valid;
		}

		$attempts = $this->getAttempts() + 1;
		if ($attempts >= self::MAX_ATTEMPTS)
		{
			$this->resetCode();
			return null;
		}

		$this->setAttempts($attempts);
		return $valid;
	}
}
