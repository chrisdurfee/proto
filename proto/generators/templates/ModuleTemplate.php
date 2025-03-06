<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

use Proto\Generators\Templates\ClassTemplate;

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
	 * Retrieves the module class name.
	 *
	 * @return string
	 */
	protected function getClassName(): string
	{
		return $this->get('className') . 'Module';
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
	 * This module handles user-related functionality.
	 *
	 * @package Modules\User
	 */
	// Add module methods and properties here.
EOT;
	}
}