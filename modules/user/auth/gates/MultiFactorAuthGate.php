<?php declare(strict_types=1);
namespace Modules\User\Auth\Gates;

use Proto\Auth\Gates\Gate;

/**
 * MultiFactorAuthGate
 *
 * Stores and validates a one‑time authentication code—plus the user
 * and device objects associated with that MFA step—inside the session.
 *
 * @package Modules\User\Auth\Gates
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
	 * @param object $user Authenticated user model.
	 * @param object $device Device/fingerprint model.
	 * @return void
	 */
	public function setResources(object $user, object $device): void
	{
		$this->setUser($user);
		$this->setDevice($device);
	}

	/**
	 * Store the user object in the session.
	 *
	 * @param object $user
	 * @return void
	 */
	public function setUser(object $user): void
	{
		$this->set(self::AUTH_USER, $user);
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
	 * Compare the provided code against the one stored in the session.
	 * If it matches, the session values are cleared.
	 *
	 * @param string $code Code entered by the user.
	 * @return bool True on success, false otherwise.
	 */
	public function validateCode(string $code): bool
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
		}
		return $valid;
	}
}
