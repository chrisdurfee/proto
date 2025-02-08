<?php declare(strict_types=1);
namespace Proto\Auth\Policies;

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
	 * @param object|null $controller The controller instance associated with this policy.
	 */
	public function __construct(protected ?object $controller = null) {}
}
