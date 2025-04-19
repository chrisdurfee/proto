<?php declare(strict_types=1);
namespace Modules\User\Controllers\Multifactor;

use Proto\Http\Request;
use Modules\User\Models\User;
use Proto\Utils\Strings;

/**
 * MultiFactorHelper
 *
 * This class provides helper methods for multi-factor authentication.
 *
 * @package Modules\User\Controllers\Multifactor
 */
abstract class MultiFactorHelper
{
	/**
	 * This will check if the device is authorized for the user.
	 *
	 * @param User $user
	 * @param object|null $device
	 * @return bool
	 */
    public static function isDeviceAuthorized(User $user, ?object $device = null): bool
	{
		if (!$device)
		{
			return false;
		}

		$model = new UserAuthedConnectionController();
		return $model->isAuthed($user->id, $device->guid, Request::ip());
	}

	/**
	 * This will get the multi-factor options for the user.
	 *
	 * @param User $user
	 * @return array
	 */
	public static function getMultiFactorOptions(User $user): array
	{
		$options = [];

		if (!empty($user->mobile))
		{
			array_push($options, (object)[
				'type' => 'sms',
				'value' => Strings::mask($user->mobile)
			]);
		}

		if (!empty($user->email))
		{
			array_push($options, (object)[
				'type' => 'email',
				'value' => self::maskEmail($user->email)
			]);
		}
		return $options;
	}

	/**
	 * This will mask the email address.
	 *
	 * @param string $email
	 * @return string
	 */
	protected static function maskEmail(string $email): string
	{
		$parts = explode('@', $email);
		return Strings::mask($parts[0]) . '@' . $parts[1];
	}
}
