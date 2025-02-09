<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

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
	 * Retrieves the extends string.
	 *
	 * @return string
	 */
	protected function getExtends(): string
	{
		$extends = $this->get('extends');
		return 'extends ' . (!empty($extends) ? $extends : 'BaseAPI');
	}

	/**
	 * Retrieves the controller type property.
	 *
	 * @return string
	 */
	protected function getControllerType(): string
	{
		$controller = $this->getControllerName();
		$property = $this->getProtectedProperty('$controllerType', "{$controller}::class", true);

		return <<<EOT
	/**
	 * @var string \$controllerType
	 */
	{$property}
EOT;
	}

	/**
	 * Retrieves the policy property.
	 *
	 * @return string
	 */
	protected function getPolicy(): string
	{
		$policy = $this->get('policy');
		if (empty($policy))
		{
			return '';
		}

		$property = $this->getProperty('public', '$policy', "{$policy}::class");

		return <<<EOT
	/**
	 * @var string \$policy
	 */
	{$property}
EOT;
	}

	/**
	 * Retrieves the API class name.
	 *
	 * @return string
	 */
	protected function getClassName(): string
	{
		return $this->get('className') . 'API';
	}

	/**
	 * Retrieves the use statements.
	 *
	 * @return string
	 */
	protected function getUse(): string
	{
		$className = $this->getNamespace() . $this->getControllerName();
		$policy = $this->getPolicyString();

		$useStatements = [];
		if (!empty($policy))
		{
			$useStatements[] = $policy;
		}
		$useStatements[] = "use App\\Controllers\\{$className};";

		return implode("\n", $useStatements);
	}

	/**
	 * Retrieves the policy use statement.
	 *
	 * @return string
	 */
	protected function getPolicyString(): string
	{
		$policy = $this->get('policy');
		if (empty($policy))
		{
			return '';
		}

		$namespace = $this->getNamespace();
		return "use App\\Auth\\Policies\\{$namespace}{$policy};";
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
	 * Retrieves the class content.
	 *
	 * @return string
	 */
	protected function getClassContent(): string
	{
		$controller = $this->getControllerType();
		$policy = $this->getPolicy();

		$content = [];
		if (!empty($controller))
		{
			$content[] = $controller;
		}
		if (!empty($policy))
		{
			$content[] = $policy;
		}

		return implode("\n\n", $content);
	}
}