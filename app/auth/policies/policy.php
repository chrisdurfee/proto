<?php declare(strict_types=1);
namespace App\Auth\Policies;

use Proto\Auth\Policies\Policy as BasePolicy;
use Proto\Http\Session;
use Proto\Http\Session\SessionInterface;
use App\Auth\UserTraitPolicy;
use App\Auth\ResourceGate;
use App\Data;
use App\Auth;

/**
 * Policy
 *
 * This is the policy class.
 *
 * @package App\Auth\Policies
 */
abstract class Policy extends BasePolicy
{
	use UserTraitPolicy;

	/**
	 * @var SessionInterface $session
	 */
	protected static SessionInterface $session;

	/**
	 * @var Data $appData
	 */
	protected static Data $appData;

	/**
	 * This will get a resource gate.
	 *
	 * @return ResourceGate
	 */
	protected function getResource(): ResourceGate
	{
		return Auth::resource();
	}

	/**
	 * This will get a value from the session.
	 *
	 * @param string $key
	 * @return mixed
	 */
	protected static function getValue(string $key): mixed
	{
		$session = self::getSession();
		return $session->{$key};
	}

	/**
	 * This will get the session.
	 *
	 * @return SessionInterface
	 */
	protected static function getSession(): SessionInterface
	{
		return self::$session ?? (self::$session = Session::getInstance());
	}

	/**
	 * This will setup the app data.
	 *
	 * @return Data
	 */
	protected static function getAppData(): Data
	{
		return self::$appData ?? (self::$appData = Data::getInstance());
	}

	/**
	 * This will get a value from the app data.
	 *
	 * @param string $key
	 * @return mixed
	 */
	protected static function getData(string $key): mixed
	{
		$data = self::getAppData();
		return $data->{$key};
	}
}
