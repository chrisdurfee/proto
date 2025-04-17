<?php declare(strict_types=1);
namespace Modules\User\Controllers\Methods;

use Proto\Http\Request;

/**
 * MultiFactorHelper
 *
 * This class provides helper methods for multi-factor authentication.
 *
 * @package Modules\User\Controllers\Methods
 */
abstract class MultiFactorHelper
{
    protected function isDeviceAuthorized(object $user, ?object $device = null): bool
	{
		if (!$device)
		{
			return false;
		}

		$model = new UserAuthedConnection();
		return $model->isAuthed($user->id, $device->guid, Request::ip());
	}
}
