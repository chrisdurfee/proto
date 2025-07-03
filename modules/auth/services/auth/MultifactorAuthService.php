<?php declare(strict_types=1);
namespace Modules\Auth\Services\Auth;

use Modules\User\Models\User;
use Proto\Dispatch\Dispatcher;
use Modules\Auth\Auth\Gates\MultiFactorAuthGate;
use Modules\Auth\Services\Auth\ConnectionDto;
use Modules\Auth\Controllers\Multifactor\UserAuthedConnectionController;

/**
 * MultiFactorAuthService
 *
 * Handles multi‑factor authentication (MFA) workflows:
 *   • Generates and stores one‑time codes via MultiFactorAuthGate
 *   • Sends codes by SMS or email
 *   • Associates user and device context with the current MFA session
 *   • Persists new authenticated connections
 *
 * @package Modules\Auth\Services\Auth
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
		$data = self::gate()->getUser();
		return ($data)? new User($data) : null;
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
	 * @return bool|null
	 */
	public function validateCode(string $code): ?bool
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
	 * @param User $user
	 * @param string $type 'sms' or 'email'
	 * @param string $code
	 * @return object
	 */
	protected function dispatchCode(User $user, string $type, string $code): object
	{
		var_dump($code);
		return $type === 'sms'
			? $this->textCode($user, $code)
			: $this->emailCode($user, $code);
	}

	/**
	 * Send the MFA code via email.
	 *
	 * @param User $user
	 * @param string $code
	 * @return object
	 */
	protected function emailCode(User $user, string $code): object
	{
		$settings = (object)[
			'to' => $user->email,
			'subject' => 'Authorization Code',
			'template' => 'Modules\\Auth\\Email\\Auth\\AuthMultiFactorEmail'
		];

		return $this->dispatchEmail($settings, (object)['code' => $code]);
	}

	/**
	 * Notify the user of a new authenticated connection via email.
	 *
	 * @param User $user
	 * @return object
	 */
	protected function emailConnection(User $user): object
	{
		$settings = (object)[
			'to' => $user->email,
			'subject' => 'New Sign-In Connection',
			'template' => 'Modules\\Auth\\Email\\Auth\\AuthNewConnectionEmail'
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
		$settings->queue = true;
		return Dispatcher::email($settings, $data);
	}

	/**
	 * Send the MFA code via SMS.
	 *
	 * @param User $user
	 * @param string $code
	 * @return object
	 */
	protected function textCode(User $user, string $code): object
	{
		$settings = (object)[
			'to' => $user->mobile,
			'template' => 'Modules\\Auth\\Text\\Auth\\AuthMultiFactorText'
		];

		return $this->dispatchText($settings, (object)['code' => $code]);
	}

	/**
	 * Notify the user of a new authenticated connection via SMS.
	 *
	 * @param User $user
	 * @return object
	 */
	protected function textConnection(User $user): object
	{
		$settings = (object)[
			'to' => $user->mobile,
			'template' => 'Modules\\Auth\\Text\\Auth\\AuthNewConnectionText'
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
		$settings->queue = true;
		return Dispatcher::sms($settings, $data);
	}
}
