<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

use Proto\Utils\Strings;

/**
 * ClassTemplate
 *
 * This class serves as a base template for generating class files.
 *
 * @package Proto\Generators\Templates
 * @abstract
 */
abstract class ClassTemplate extends Template
{
	/**
	 * Whether strict types should be declared.
	 *
	 * @var bool
	 */
	protected bool $useStrict = true;

	/**
	 * Whether the class should be marked as final.
	 *
	 * @var bool
	 */
	protected bool $final = false;

	/**
	 * Returns the strict type declaration if enabled.
	 *
	 * @return string
	 */
	protected function getUseStrict(): string
	{
		return $this->useStrict ? "declare(strict_types=1);\n\n" : '';
	}

	/**
	 * Returns the final keyword if enabled.
	 *
	 * @return string
	 */
	protected function getFinal(): string
	{
		return $this->final ? 'final ' : '';
	}

	/**
	 * Retrieves the directory path for the class.
	 *
	 * @return string
	 */
	protected function getDir(): string
	{
		return 'Common\\' . $this->get('dir');
	}

	/**
	 * Retrieves the file namespace.
	 *
	 * @return string
	 */
	protected function getFileNamespace(): string
	{
		return "namespace " . $this->getDir() . ";\n";
	}

	/**
	 * Retrieves the class content.
	 *
	 * @return string
	 */
	protected function getClassContent(): string
	{
		return '';
	}

	/**
	 * Generates a property string.
	 *
	 * @param string $privacy Property visibility (public, protected, private).
	 * @param string $key Property name.
	 * @param string $value Property value.
	 * @param string $type Property type.
	 * @return string
	 */
	protected function getProperty(string $privacy, string $key, string $value, string $type = ''): string
	{
		$type = $type ? " {$type}" : '';
		return "{$privacy}{$type} {$key} = {$value};\n";
	}

	/**
	 * Returns a quoted value if it's a string.
	 *
	 * @param mixed $value The value to quote.
	 * @return string
	 */
	protected function quote(mixed $value): string
	{
		return is_string($value) ? "'{$value}'" : (string)$value;
	}

	/**
	 * Generates a protected property string.
	 *
	 * @param string $key Property name.
	 * @param string $value Property value.
	 * @param bool $static Whether the property is static.
	 * @param string $type Property type.
	 * @return string
	 */
	protected function getProtectedProperty(string $key, string $value, bool $static = false, string $type = ''): string
	{
		$privacy = 'protected' . ($static ? ' static' : '');
		return $this->getProperty($privacy, $key, $value, $type);
	}

	/**
	 * Retrieves the extends string if applicable.
	 *
	 * @return string
	 */
	protected function getExtends(): string
	{
		$extends = $this->get('extends');
		return !empty($extends) ? " extends {$extends}" : '';
	}

	/**
	 * Retrieves the abstract keyword if applicable.
	 *
	 * @return string
	 */
	protected function getAbstract(): string
	{
		return $this->get('abstract') ? 'abstract ' : '';
	}

	/**
	 * Retrieves the class name.
	 *
	 * @return string
	 */
	protected function getClassName(): string
	{
		$className = $this->get('className');
		return Strings::pascalCase($className);
	}

	/**
	 * Retrieves the namespace string.
	 *
	 * @return string
	 */
	protected function getNamespace(): string
	{
		$namespace = $this->get('namespace');
		return !empty($namespace) ? "{$namespace}\\" : '';
	}

	/**
	 * Retrieves the use statements.
	 *
	 * @return string
	 */
	protected function getUse(): string
	{
		$use = $this->get('use');
		return !empty($use) ? "{$use}\n" : '';
	}

	/**
	 * This will get the package string.
	 *
	 * @return string
	 */
	protected function getPackage(): string
	{
		return "
 * @package {$this->getDir()}";
	}

	/**
	 * Generates the class body.
	 *
	 * @return string
	 */
	protected function getBody(): string
	{
		$useStrict = $this->getUseStrict();
		$namespace = $this->getFileNamespace();
		$use = $this->getUse();
		$package = $this->getPackage();
		$final = $this->getFinal();
		$abstract = $this->getAbstract();
		$className = $this->getClassName();
		$extends = $this->getExtends();

		return <<<EOT
<?php {$useStrict}
{$namespace}
{$use}

/**
 * {$className}
 * {$package}
 */
{$final}{$abstract}class {$className} {$extends}
{
{$this->getClassContent()}
}
EOT;
	}
}