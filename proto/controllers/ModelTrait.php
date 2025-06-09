<?php declare(strict_types=1);
namespace Proto\Controllers;

/**
 * ModelTrait
 *
 * This trait provides model-related functionality for controllers.
 *
 * @package Proto\Controllers
 */
trait ModelTrait
{
	/**
	 * @var string|null $model The model class reference using ::class.
	 */
	protected ?string $model = null;

	/**
	 * Retrieves the model class name.
	 *
	 * @return string|null The model class reference using ::class.
	 */
	protected function getModelClass(): ?string
	{
		return $this->model;
	}

	/**
	 * Sets the model class if not already set.
	 *
	 * @return void
	 */
	protected function setModelClass(): void
	{
		if ($this->model === null)
		{
			$this->model = $this->getModelClass();
		}
	}

	/**
	 * Creates and returns a new model instance.
	 *
	 * @param object|null $data The model data.
	 * @return object|null The model instance or null if no class is set.
	 */
	protected function model(?object $data = null): ?object
	{
		return $this->model ? new ($this->model)($data) : null;
	}

	/**
	 * Retrieves the model storage instance.
	 *
	 * @param object|null $data The model data.
	 * @return object|null The storage instance or null if no model exists.
	 */
	protected function storage(?object $data = null): ?object
	{
		return $this->model($data)?->storage();
	}

	/**
	 * Handles dynamic method calls, forwarding them to the model.
	 *
	 * @param string $method The method name.
	 * @param array $arguments The method arguments.
	 * @return mixed The result of the method call.
	 */
	public function __call(string $method, array $arguments): mixed
	{
		$model = $this->model();
		$callable = [$model, $method];

		if (!\is_callable($callable))
		{
			return $this->error('The method is not callable.');
		}

		$result = \call_user_func_array($callable, $arguments);
		return $this->response(is_array($result) ? ['rows' => $result] : ['row' => $result]);
	}

	/**
	 * Handles static method calls, forwarding them to the model.
	 *
	 * @param string $method The method name.
	 * @param array $arguments The method arguments.
	 * @return mixed The result of the method call.
	 */
	public static function __callStatic(string $method, array $arguments): mixed
	{
		$controller = new static();
		$modelClass = $controller->getModelClass();

		if (!\is_callable([$modelClass, $method]))
		{
			return Response::errorResponse('The method is not callable.');
		}

		return \call_user_func_array([$modelClass, $method], $arguments);
	}
}