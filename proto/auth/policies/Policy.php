<?php declare(strict_types=1);
namespace Proto\Auth\Policies;

use Proto\Controllers\ControllerInterface;

/**
 * Class Policy
 *
 * Base class for authentication policies.
 *
 * @package Proto\Auth\Policies
 * @abstract
 */
abstract class Policy
{
	/**
	 * This will create a new instance of the policy.
	 *
	 * @param ?ControllerInterface $controller The controller instance associated with this policy.
	 * @return void
	 */
	public function __construct(protected ?ControllerInterface $controller = null) {}
}
