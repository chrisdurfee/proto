<?php declare(strict_types=1);
namespace Proto\Generators\Templates;

/**
 * ServiceTemplate
 *
 * Generates a service class.
 *
 * @package Proto\Generators\Templates
 */
class ServiceTemplate extends ClassTemplate
{
	/**
	 * Retrieves the extends string.
	 *
	 * @return string
	 */
	protected function getExtends(): string
	{
		$extends = $this->get('extends');
		return 'extends ' . (!empty($extends) ? $extends : 'Service');
	}

	/**
	 * Retrieves the service class name.
	 *
	 * @return string
	 */
	protected function getClassName(): string
	{
		return $this->get('className') . 'Service';
	}

	/**
	 * Retrieves the use statements.
	 *
	 * @return string
	 */
	protected function getUse(): string
	{
		$dir = $this->getModuleDir();
		$className = $this->getNamespace() . $this->get('className');
		return "use Proto\\Services\\Service;
use Proto\\Services\\ServiceResult;
use {$dir}\\Models\\{$className};";
	}

	/**
	 * Retrieves the class content.
	 *
	 * @return string
	 */
	protected function getClassContent(): string
	{
		$className = $this->get('className');

		return <<<EOT
	/**
	 * Create a {$className}.
	 *
	 * @param object \$data
	 * @return ServiceResult
	 */
	public function add(object \$data): ServiceResult
	{
		\$model = new {$className}(\$data);
		return \$model->add()
			? \$this->success(\$model)
			: \$this->failure('Unable to add {$className}');
	}

	/**
	 * Update a {$className}.
	 *
	 * @param object \$data
	 * @return ServiceResult
	 */
	public function update(object \$data): ServiceResult
	{
		\$model = new {$className}(\$data);
		return \$model->update()
			? \$this->success(\$model)
			: \$this->failure('Unable to update {$className}');
	}

	/**
	 * Delete a {$className}.
	 *
	 * @param object \$data
	 * @return ServiceResult
	 */
	public function delete(object \$data): ServiceResult
	{
		\$model = new {$className}(\$data);
		return \$model->delete()
			? \$this->success(true)
			: \$this->failure('Unable to delete {$className}');
	}
EOT;
	}
}
