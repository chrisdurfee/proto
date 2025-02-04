<?php declare(strict_types=1);
namespace Proto\Auth\Policies;

/**
 * Policy
 *
 * This will be the base class for all auth policies.
 *
 * @package Proto\Auth\Policies
 * @abstract
 */
abstract class Policy
{
    /**
     * @var object|null $controller
     */
    protected $controller = null;

    /**
     * This will add the controller to the policy.
     *
     * @param object|null $controller
     * @return void
     */
    public function __construct(?object $controller = null)
    {
        $this->controller = $controller;
    }
}
