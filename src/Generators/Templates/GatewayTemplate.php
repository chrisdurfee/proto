<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

use Proto\Generators\Templates\ClassTemplate;
use Proto\Utils\Strings;

/**
 * GatewayTemplate
 *
 * This template generates a Gateway class.
 *
 * @package Proto\Generators\Templates
 */
class GatewayTemplate extends ClassTemplate
{
	/**
	 * Retrieves the extends string.
	 *
	 * @return string
	 */
	protected function getExtends(): string
	{
		$extends = $this->get('extends');
		return !empty($extends) ? 'extends ' . $extends : '';
	}

	/**
     * Retrieves the module name.
     *
     * @return string
     */
    protected function getModuleName(): string
    {
        return Strings::pascalCase($this->get('moduleName') ?? '');
    }

	/**
	 * Retrieves the gateway class name.
	 *
	 * @return string
	 */
	protected function getClassName(): string
	{
		return 'Gateway';
	}

	/**
	 * Retrieves the use statement for the gateway.
	 *
	 * @return string
	 */
	protected function getUse(): string
	{
		return '';
	}

	/**
	 * Retrieves the directory path for the class.
	 *
	 * @return string
	 */
	protected function getDir(): string
	{
        $moduleName = $this->getModuleName();
		return "Modules\\" . $moduleName . "\\Gateway";
	}

	/**
	 * Retrieves the class content.
	 *
	 * @return string
	 */
	protected function getClassContent(): string
	{
		return <<<EOT
	// Add gateway methods and properties here.
EOT;
	}
}