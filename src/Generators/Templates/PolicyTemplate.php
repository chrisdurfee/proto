<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

/**
 * PolicyTemplate
 *
 * This will create a policy template generator.
 *
 * @package Proto\Generators\Templates
 */
class PolicyTemplate extends ClassTemplate
{
	/**
	 * This will get the className.
	 *
	 * @return string
	 */
	protected function getClassName(): string
	{
		return $this->get('className') . 'Policy';
	}

	/**
	 * This will get the extends string.
	 *
	 * @return string
	 */
	protected function getExtends(): string
	{
		$extends = $this->get('extends');
		return 'extends ' . (($extends)? $extends : 'Policy');
	}

	/**
	 * Retrieves the use statement for the model.
	 *
	 * @return string
	 */
	protected function getUse(): string
	{
		return "use Proto\\Auth\\Policies\\Policy;";
	}

	/**
	 * This will get the class content.
	 *
	 * @return string
	 */
	protected function getClassContent(): string
	{
		return <<<EOT

EOT;
	}
}
