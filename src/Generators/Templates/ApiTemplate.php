<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

use Proto\Utils\Strings;

/**
 * ApiTemplate
 *
 * This class generates an API template.
 *
 * @package Proto\Generators\Templates
 */
class ApiTemplate extends ClassTemplate
{
	/**
	 * Retrieves the use statements.
	 *
	 * @return string
	 */
	protected function getUse(): string
	{
		$className = $this->getNamespace() . $this->getControllerName();
		$dir = $this->getModuleDir();

		$useStatements = [];
		$useStatements[] = "use {$dir}\\Controllers\\{$className};";

		return implode("\n", $useStatements);
	}

	/**
	 * Retrieves the controller name.
	 *
	 * @return string
	 */
	protected function getControllerName(): string
	{
		$className = $this->get('className');
		return "{$className}Controller";
	}

	/**
	 * Builds the resource path including module name and feature path or namespace.
	 *
	 * Uses featurePath if available, otherwise falls back to namespace.
	 *
	 * @return string
	 */
	protected function getResourcePath(): string
	{
		$className = $this->getClassName();
		$path = Strings::hyphen($className);

		// Prefer feature path over namespace
		$featurePath = $this->get('featurePath') ?? '';
		if (!empty($featurePath))
		{
			// Convert feature path to lowercase with forward slashes
			$featurePath = strtolower($featurePath);
			$featurePath = str_replace('\\', '/', $featurePath);
			return "{$path}/{$featurePath}";
		}

		// Fallback to namespace if no feature path
		$namespace = $this->get('namespace') ?? '';
		if (!empty($namespace))
		{
			// Convert namespace to lowercase with forward slashes
			$namespace = strtolower($namespace);
			$namespace = str_replace('\\', '/', $namespace);
			return "{$path}/{$namespace}";
		}

		// No additional path segments
		return $path;
	}

	/**
	 * Generates the class body.
	 *
	 * @return string
	 */
	protected function getBody(): string
	{
		$namespace = $this->getFileNamespace();
		$use = $this->getUse();
		$className = $this->getClassName();
		$controllerName = $this->getControllerName();
		$path = $this->getResourcePath();

		return <<<EOT
<?php declare(strict_types=1);
{$namespace}
{$use}

/**
 * {$className} Routes
 *
 * This file contains the API routes for the {$className} module.
 */
router()
	->resource('{$path}', {$controllerName}::class);
EOT;
	}
}