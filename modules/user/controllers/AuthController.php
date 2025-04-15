<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Proto\Auth\Gates\CrossSiteRequestForgeryGate;
use Proto\Controllers\Controller;

/**
 * AuthController
 *
 * This will handle the authentication process.
 *
 * @package Modules\User\Controllers
 */
class AuthController extends Controller
{
	/**
	 * This will get the CSRF token.
	 *
	 * @return void
	 */
	public function getToken(): object
	{
		$gate = new CrossSiteRequestForgeryGate();
		$token = $gate->setToken();

		return (object)[
			'token' => $token
		];
	}
}