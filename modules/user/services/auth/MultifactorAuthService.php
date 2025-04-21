<?php declare(strict_types=1);
namespace Modules\User\Services\Auth;

use Modules\User\Models\User;
use Proto\Dispatch\Enqueuer;
use Modules\User\Auth\Gates\MultiFactorAuthGate;
use Modules\User\Services\Auth\ConnectionDto;
use Modules\User\Controllers\Multifactor\UserAuthedConnectionController;

/**
 * MultiFactorAuthService
 *
 * Handles multi‑factor authentication (MFA) workflows:
 *   • Generates and stores one‑time codes via MultiFactorAuthGate
 *   • Sends codes by SMS or email
 *   • Associates user and device context with the current MFA session
 *   • Persists new authenticated connections
 *
 * @package Modules\User\Services\Auth
 */
class MultiFactorAuthService
{
	/**
	 * Singleton instance of the MFA gate.
	 *
	 * @var MultiFactorAuthGate|null
	 */
	protected static ?MultiFactorAuthGate $gate = null;

	/**
	 * Lazily retrieve the MFA gate.
	 *
	 * @return MultiFactorAuthGate
	 */
	protected static function gate(): MultiFactorAuthGate
	{
		return self::$gate ?? (self::$gate = new MultiFactorAuthGate());
	}

	/**
	 * Generate a new MFA code and dispatch it to the user.
	 *
	 * @param User $user User model containing email/mobile.
	 * @param string $type Delivery channel: 'sms' (default) or 'email'.
	 * @return void
	 */
	public function sendCode(User $user, string $type = 'sms'): void
	{
		$code = self::gate()->setCode();
		$this->dispatchCode($user, $type, $code);
	}

	/**
	 * Persist user and device context for this MFA session.
	 *
	 * @param User $user
	 * @param object $device
	 * @return void
	 */
	public function setResources(User $user, object $device): void
	{
		self::gate()->setResources($user, $device);
	}

	/**
	 * Retrieve the user stored for this MFA session.
	 *
	 * @return object|null
	 */
	public function getUser(): ?object
	{
		return self::gate()->getUser();
	}

	/**
	 * Retrieve the device stored for this MFA session.
	 *
	 * @return object|null
	 */
	public function getDevice(): ?object
	{
		return self::gate()->getDevice();
	}

	/**
	 * Validate a user‑supplied MFA code.
	 * Code is cleared from the session on first successful match.
	 *
	 * @param string $code
	 * @return bool
	 */
	public function validateCode(string $code): bool
	{
		return self::gate()->validateCode($code);
	}

	/**
	 * This will add a connection.
	 *
	 * @param User $user
	 * @param object $device
	 * @param string $ipAddress
	 * @return object
	 */
	public function addNewConnection(User $user, object $device, string $ipAddress): object
	{
		$connection = ConnectionDto::create($device, $user->id, $ipAddress);
        return $this->authConnection($connection);
	}

	/**
	 * Record a newly authenticated connection (IP, device, location).
	 *
	 * @param object $connection
	 * @return object Persisted connection model.
	 */
	public function authConnection(object $connection): object
	{
		$controller = new UserAuthedConnectionController();
		return $controller->setup($connection);
	}

	/**
	 * Route the code through the chosen messaging channel.
	 *
	 * @param object $user
	 * @param string $type 'sms' or 'email'
	 * @param string $code
	 * @return object
	 */
	protected function dispatchCode(object $user, string $type, string $code): object
	{
		return $type === 'sms'
			? $this->textCode($user, $code)
			: $this->emailCode($user, $code);
	}

	/**
	 * Send the MFA code via email.
	 *
	 * @param object $user
	 * @param string $code
	 * @return object
	 */
	protected function emailCode(object $user, string $code): object
	{
		$settings = (object)[
			'to' => $user->email,
			'subject' => 'Authorization Code',
			'template' => 'Modules\\User\\Email\\Auth\\AuthMultiFactorEmail'
		];

		return $this->dispatchEmail($settings, (object)['code' => $code]);
	}

	/**
	 * Notify the user of a new authenticated connection via email.
	 *
	 * @param object $user
	 * @return object
	 */
	protected function emailConnection(object $user): object
	{
		$settings = (object)[
			'to' => $user->email,
			'subject' => 'New Sign-In Connection',
			'template' => 'Modules\\User\\Email\\Auth\\AuthNewConnectionEmail'
		];

		return $this->dispatchEmail($settings);
	}

	/**
	 * Queue an email through the application’s enqueue system.
	 *
	 * @param object $settings Message meta (to, subject, template).
	 * @param object|null $data Template variables.
	 * @return object
	 */
	protected function dispatchEmail(object $settings, ?object $data = null): object
	{
		return Enqueuer::email($settings, $data);
	}

	/**
	 * Retrieve the default SMS session ID from configuration.
	 *
	 * @return string
	 */
	protected function getSmsSession(): string
	{
		$sms = env('sms');
		return $sms->fromSendId;
	}

	/**
	 * Send the MFA code via SMS.
	 *
	 * @param object $user
	 * @param string $code
	 * @return object
	 */
	protected function textCode(object $user, string $code): object
	{
		$settings = (object)[
			'to' => $user->mobile,
			'session' => $this->getSmsSession(),
			'template' => 'Modules\\User\\Text\\Auth\\AuthMultiFactorText'
		];

		return $this->dispatchText($settings, (object)['code' => $code]);
	}

	/**
	 * Notify the user of a new authenticated connection via SMS.
	 *
	 * @param object $user
	 * @return object
	 */
	protected function textConnection(object $user): object
	{
		$settings = (object)[
			'to' => $user->mobile,
			'session' => $this->getSmsSession(),
			'template' => 'Modules\\User\\Text\\Auth\\AuthNewConnectionText'
		];

		return $this->dispatchText($settings);
	}

	/**
	 * Queue an SMS through the application’s enqueue system.
	 *
	 * @param object $settings Message meta (to, session, template).
	 * @param object|null $data Template variables.
	 * @return object
	 */
	protected function dispatchText(object $settings, ?object $data = null): object
	{
		return Enqueuer::sms($settings, $data);
	}
}
