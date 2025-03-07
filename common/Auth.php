<?php declare(strict_types=1);
namespace Common;

use Proto\Auth\Gates\CrossSiteRequestForgeryGate;

/**
 * Auth
 *
 * This will allow authentication to be handled.
 *
 * @package Common
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
