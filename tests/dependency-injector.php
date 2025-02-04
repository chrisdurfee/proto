<?php declare(strict_types=1);
namespace Tests;

/**
 * DependencyInjector
 *
 * This class provides functionality for testing with mock dependencies.
 *
 * @package Tests
 */
class DependencyInjector
{
    /**
     * This will convert a dependency array to the params of the class constructor
     * and return the instantiated object.
     *
     * This is used for dynamic dependency injection.
     *
     * @param array $depends
     * @param string $className
     * @return object
     */
    public static function convert(string $className, array $depends): object
    {
        $reflection = new \ReflectionClass($className);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();

        $classParams = [];
        for ($i = 0; $i < count($parameters); $i++)
        {
            $param = $parameters[$i];
            $name = $param->getName();
            $defaultValue = $param->getDefaultValue();

            $classParams[] = $depends[$name] ?? $defaultValue;
        }

        return new $className(...$classParams);
    }
}