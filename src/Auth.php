<?php declare(strict_types=1);
namespace Proto
{
	use Proto\Patterns\Structural\Registry;

	/**
	 * Auth
	 *
	 * This will allow authentication to be handled.
	 *
	 * @package Common
	 */
	class Auth extends Registry
	{
	}
}

namespace
{
	use Proto\Auth;

	/**
	 * This will get the instance of Auth.
	 *
	 * @return Auth
	 */
	function auth(): Auth
	{
		return Auth::getInstance();
	}
}
