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
		$path = Strings::hyphen($className);

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