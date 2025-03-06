<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

use Proto\Generators\Templates\ClassTemplate;

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
	 * Retrieves the class content.
	 *
	 * @return string
	 */
	protected function getClassContent(): string
	{
		return <<<EOT

	/**
	 * This will handle the user module gateway.
	 *
	 * @package Modules\User\Gateway
	 */
	// Add gateway methods and properties here.
EOT;
	}
}