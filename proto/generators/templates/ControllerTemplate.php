<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

/**
 * ControllerTemplate
 *
 * This class generates a controller template.
 *
 * @package Proto\Generators\Templates
 */
class ControllerTemplate extends ClassTemplate
{
	/**
	 * Retrieves the extends string.
	 *
	 * @return string
	 */
	protected function getExtends(): string
	{
		$extends = $this->get('extends');
		return 'extends ' . (!empty($extends) ? $extends : 'Controller');
	}

	/**
	 * Retrieves the model class constructor.
	 *
	 * @return string
	 */
	protected function getModelClass(): string
	{
		$className = $this->get('className');

		return <<<EOT
	/**
	 * Initializes the model class.
	 *
	 * @param string|null \$modelClass The model class reference using ::class.
	 */
	public function __construct(protected ?string \$modelClass = {$className}::class)
	{
		parent::__construct(\$modelClass);
	}
EOT;
	}

	/**
	 * Retrieves the controller class name.
	 *
	 * @return string
	 */
	protected function getClassName(): string
	{
		return $this->get('className') . 'Controller';
	}

	/**
	 * Retrieves the use statement for the model.
	 *
	 * @return string
	 */
	protected function getUse(): string
	{
		$className = $this->getNamespace() . $this->get('className');
		return "use App\\Models\\{$className};";
	}

	/**
	 * Retrieves the class content.
	 *
	 * @return string
	 */
	protected function getClassContent(): string
	{
		$model = $this->getModelClass();

		return <<<EOT
	/**
	 * Determines whether to pass response.
	 *
	 * @var bool
	 */
	protected bool \$passResponse = true;

	{$model}
EOT;
	}
}