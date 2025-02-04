<?php declare(strict_types=1);
namespace Proto\API;

/**
 * ServiceInterface
 *
 * This will be the base interface for all api services.
 *
 * @package Proto\API
 */
interface ServiceInterface
{
    /**
     * This will check if a method is excludeed to CSRF checks.
     *
     * @param string $methodName
     * @return bool
     */
    public function isOpenMethod(string $methodName): bool;
}