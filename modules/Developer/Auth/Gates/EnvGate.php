<?php declare(strict_types=1);
namespace Modules\Developer\Auth\Gates;

use Proto\Auth\Gates\Gate;

/**
 * EnvGate
 *
 * This will create an environment-based access control gate.
 *
 * @package Modules\Developer\Auth\Gates
 */
class EnvGate extends Gate
{
	/**
	 * Checks if the user is in the development environment.
	 *
	 * @return bool True if the user is in the dev environment, otherwise false.
	 */
	public function isDev(): bool
	{
		return (env('env') === 'dev');
	}
}