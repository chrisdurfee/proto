<?php declare(strict_types=1);
namespace Modules\Developer\Auth\Policies;

use Modules\Developer\Auth\Gates\EnvGate;
use Proto\Auth\Policies\Policy;
use Proto\Controllers\ControllerInterface;

/**
 * Class DeveloperPolicy
 *
 * Policy that governs access control for developer actions.
 *
 * @package Modules\Developer\Auth\Policies
 */
class DeveloperPolicy extends Policy
{
	/**
	 * This will create a new instance of the policy.
	 *
	 * @param ?ControllerInterface $controller The controller instance associated with this policy.
	 * @param EnvGate $gate The environment gate instance for access control.
	 * @return void
	 */
	public function __construct(
		protected ?ControllerInterface $controller = null,
		protected EnvGate $gate = new EnvGate()
	)
	{
		parent::__construct($controller);
	}

	/**
	 * Default policy for methods that don't have an explicit policy method.
	 *
	 * @return bool True if the user can view users, otherwise false.
	 */
	public function default(): bool
	{
		return $this->gate->isDev();
	}
}