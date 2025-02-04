<?php declare(strict_types=1);
namespace App;

use App\Auth\UserGate;
use App\Auth\ResourceGate;
use App\Auth\MultiFactorAuthGate;
use Proto\Auth\CrossSiteRequestForgeryGate;

/**
 * Auth
 *
 * This will allow authentication to be handled.
 *
 * @package App
 */
class Auth
{
	/**
	 * This will get a CSRF gate instance.
	 *
	 * @return CrossSiteRequestForgeryGate
	 */
	public static function csrf(): CrossSiteRequestForgeryGate
	{
		return new CrossSiteRequestForgeryGate();
	}
}
