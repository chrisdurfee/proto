<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

/**
 * ClassTemplate
 *
 * This will create a base class to extend tempates to generate
 * class files.
 *
 * @package Proto\Generators\Templates
 * @abstract
 */
abstract class ClassTemplate extends Template
{
    /**
     * @var bool $useStrict
     */
    protected bool $useStrict = true;

    /**
     * @var bool $final
     */
    protected bool $final = false;

    /**
     * This will get the use strict is set.
     *
     * @return string
     */
    protected function getUseStrict(): string
    {
        return ($this->useStrict)? ' declare(strict_types=1);' : '';
    }

    /**
     * This will get the final string.
     *
     * @return string
     */
    protected function getFinal(): string
    {
        return ($this->final)? 'final ' : '';
    }

    /**
     * This will get the app directory.
     *
     * @return string
     */
    protected function getDir(): string
    {
        return 'App\\' . $this->get('dir');
    }

    /**
     * This will get the file namespace.
     *
     * @return string
     */
    protected function getFileNamespace(): string
    {
        $dir = $this->getDir();
        return "namespace {$dir};";
    }

    /**
     * This will get the class content.
     *
     * @return string
     */
    protected function getClassContent(): string
    {
        return '';
    }

    /**
     * This will create a property string.
     *
     * @param string $privacy
     * @param string $key
     * @param string $value
     * @return string
     */
    protected function getProperty(string $privacy, string $key, string $value): string
    {
        return <<<EOT
{$privacy} {$key} = {$value};

EOT;
    }

    /**
     * This will quote a value.
     *
     * @param mixed $value
     */
    protected function quote(mixed $value): string
    {
        return '\'' . $value . '\'';
    }

    /**
     * This will create a protected property string.
     *
     * @param string $key
     * @param string $value
     * @return string
     */
    protected function getProtectedProperty(string $key, string $value, bool $static = false): string
    {
        $privacy = 'protected' . (($static)? ' static' : '');
        return $this->getProperty($privacy, $key, $value);
    }

    /**
     * This will get the extends string.
     *
     * @return string
     */
    protected function getExtends(): string
    {
        $extends = $this->get('extends');
        return ($extends)? "extends {$extends}" : '';
    }

    /**
     * This will get the abstract string.
     *
     * @return string
     */
    protected function getAbstract(): string
    {
        $abstract = $this->get('abstract');
        return ($abstract)? 'abstract ' : '';
    }

    /**
     * This will get the className.
     *
     * @return string
     */
    protected function getClassName(): string
    {
        return $this->get('className');
    }

    /**
     * This will get the namespace string.
     *
     * @return string
     */
    protected function getNamespace(): string
    {
        $namespace = $this->get('namespace');

        return (empty($namespace))? '' : <<<EOT
{$namespace}\\
EOT;
    }

    /**
     * This will get the use string.
     *
     * @return string
     */
    protected function getUse(): string
    {
        $use = $this->get('use');
        return ($use)? $use : '';
    }

    /**
     * This will get the body.
     *
     * @return string
     */
	protected function getBody(): string
    {
        // namespace
        $useStrict = $this->getUseStrict();
        $namespace = $this->getFileNamespace();

        // use
        $use = $this->getUse();

        // class
        $final = $this->getFinal();
        $abstract = $this->getAbstract();
        $className = $this->getClassName();
        $extends = $this->getExtends();

        return <<<EOT
<?php{$useStrict}
{$namespace}

{$use}

/**
 * {$className}
 *
 * @package {$this->getDir()}
 */
{$final}{$abstract}class {$className} {$extends}
{
{$this->getClassContent()}
}
EOT;
    }
}
