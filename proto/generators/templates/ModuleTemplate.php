<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

use Proto\Generators\Templates\ClassTemplate;
use Proto\Utils\Strings;

/**
 * ModuleTemplate
 *
 * This template generates a module class.
 *
 * @package Proto\Generators\Templates
 */
class ModuleTemplate extends ClassTemplate
{
	/**
	 * Retrieves the extends string.
	 *
	 * @return string
	 */
	protected function getExtends(): string
	{
		return 'extends Module';
	}

    /**
     * Retrieves the module name.
     *
     * @return string
     */
    protected function getModuleName(): string
    {
        return Strings::pascalCase($this->get('name') ?? '');
    }

	/**
	 * Retrieves the module class name.
	 *
	 * @return string
	 */
	protected function getClassName(): string
	{
        $moduleName = $this->getModuleName();
		return $moduleName . 'Module';
	}

    /**
	 * Retrieves the directory path for the class.
	 *
	 * @return string
	 */
	protected function getDir(): string
	{
        $moduleName = $this->getModuleName();
		return $this->get('dir') . "\\" . $moduleName;
	}

	/**
	 * Retrieves the use statement for the module.
	 *
	 * @return string
	 */
	protected function getUse(): string
	{
		return "use Proto\\Module\\Module;";
	}

	/**
	 * Retrieves the class content.
	 *
	 * @return string
	 */
	protected function getClassContent(): string
	{
		return <<<EOT

	/**
	 * This will activate the module.
	 *
	 * @return void
	 */
	public function activate(): void
	{
		// do something
	}
EOT;
	}
}