<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

/**
 * ApiTemplate
 *
 * This will create an api template generator.
 *
 * @package Proto\Generators\Templates
 */
class ApiTemplate extends ClassTemplate
{
    /**
     * This will get the extends string.
     *
     * @return string
     */
    protected function getExtends(): string
    {
        $extends = $this->get('extends');
        return 'extends ' . (($extends)? $extends : 'BaseAPI');
    }

    /**
     * This will get the controller class string.
     *
     * @return string
     */
    protected function getControllerType(): string
    {
        $controller = $this->getControllerName();
        $property = $this->getProtectedProperty('$controllerType', $controller . '::class', true);

        return <<<EOT
/**
     * @var string \$controllerType
     */
    $property
EOT;
    }

    /**
     * This will get the policy string.
     *
     * @return string
     */
    protected function getPolicy(): string
    {
        $policy = $this->get('policy');
        if (!$policy)
        {
            return '';
        }

        $property = $this->getProperty('public', '$policy', $policy . '::class');

        return <<<EOT
/**
     * @var string \$policy
     */
    $property
EOT;
    }

    /**
     * This will get the className.
     *
     * @return string
     */
    protected function getClassName(): string
    {
        return $this->get('className') . 'API';
    }

    /**
     * This will get the use string.
     *
     * @return string
     */
    protected function getUse(): string
    {
        $className = $this->getNamespace() . $this->getControllerName();
        $policy = $this->getPolicyString();

        return <<<EOT
{$policy}
use App\\Controllers\\{$className};
EOT;
    }

    /**
     * This will get the policy string.
     *
     * @return string
     */
    protected function getPolicyString(): string
    {
        $policy = $this->get('policy');
        if (!$policy)
        {
            return '';
        }

        $namespace = $this->getNamespace();
        return 'use App\\Auth\\Policies\\' . $namespace . $policy . ';';
    }

    /**
     * This will get the controller name.
     *
     * @return string
     */
    protected function getControllerName(): string
    {
        $className = $this->get('className');
        return $className . 'Controller';
    }

    /**
     * This will get the class content.
     *
     * @return string
     */
    protected function getClassContent(): string
    {
        $controller = $this->getControllerType();
        $policy = $this->getPolicy();

        return <<<EOT
    {$controller}
    {$policy}
EOT;
    }
}
