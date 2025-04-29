<?php declare(strict_types=1);
namespace Proto\Controllers;

/**
 * ModelController
 *
 * This base controller provides a structured way to handle CRUD operations
 * for models by extending child controllers.
 *
 * @package Proto\Controllers
 * @abstract
 */
abstract class ModelController extends Controller
{
	/**
	 * @var string|null $modelClass The model class reference using ::class.
	 */
	protected ?string $modelClass = null;

	/**
	 * Initializes the model controller.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setModelClass();
	}

	/**
	 * Retrieves the model class name.
	 *
	 * @return string|null The model class reference using ::class.
	 */
	protected function getModelClass(): ?string
	{
		return $this->modelClass;
	}

	/**
	 * Sets the model class if not already set.
	 *
	 * @return void
	 */
	protected function setModelClass(): void
	{
		if ($this->modelClass === null)
		{
			$this->modelClass = $this->getModelClass();
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
		return $this->modelClass ? new ($this->modelClass)($data) : null;
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

	/**
	 * Sets up model data.
	 *
	 * @param object $data The model data.
	 * @return object The response.
	 */
	public function setup(object $data): object
	{
		$model = $this->model($data);
		return $model->setup()
			? $this->response(['success' => true, 'id' => $model->id])
			: $this->error('Unable to add the item.');
	}

	/**
	 * Adds or updates an item.
	 *
	 * @param object $data The model data.
	 * @return object The response.
	 */
	public static function put(object $data): object
	{
		return (new static())->setup($data);
	}

	/**
	 * Adds a model entry.
	 *
	 * @param object $data The model data.
	 * @return object The response.
	 */
	public function add(object $data): object
	{
		$model = $this->model($data);
		return $model->add()
			? $this->response(['success' => true, 'id' => $model->id])
			: $this->error('Unable to add the item.');
	}

	/**
	 * Adds an item.
	 *
	 * @param object $data The model data.
	 * @return object The response.
	 */
	public static function create(object $data): object
	{
		return (new static())->add($data);
	}

	/**
	 * Merges model data.
	 *
	 * @param object $data The model data.
	 * @return object The response.
	 */
	public function merge(object $data): object
	{
		$model = $this->model($data);
		return $model->merge()
			? $this->response(['success' => true, 'id' => $model->id])
			: $this->error('Unable to merge the item.');
	}

	/**
	 * Updates model item status.
	 *
	 * @param int $id The model ID.
	 * @param mixed $status The status value.
	 * @return object The response.
	 */
	public function updateStatus(int $id, mixed $status): object
	{
		return $this->response(
			$this->model((object) ['id' => $id, 'status' => $status])->updateStatus()
		);
	}

	/**
	 * Updates model data.
	 *
	 * @param object $data The model data.
	 * @return object The response.
	 */
	public function update(object $data): object
	{
		return $this->response($this->model($data)->update());
	}

	/**
	 * Edits an item.
	 *
	 * @param object $data The model data.
	 * @return object The response.
	 */
	public static function edit(object $data): object
	{
		return (new static())->update($data);
	}

	/**
	 * Deletes model data.
	 *
	 * @param int|object $data The model ID or object.
	 * @return object The response.
	 */
	public function delete(int|object $data): object
	{
		$id = is_object($data) ? $data->id : $data;
		return $this->response(
			$this->model((object) ['id' => $id])->delete()
		);
	}

	/**
	 * Removes an item.
	 *
	 * @param object $data The model data.
	 * @return object The response.
	 */
	public static function remove(object $data): object
	{
		return (new static())->delete($data);
	}

	/**
	 * Retrieves a model by ID.
	 *
	 * @param mixed $id The model ID.
	 * @return object The response.
	 */
	public function get(mixed $id): object
	{
		return $this->response(['row' => $this->modelClass::get($id)]);
	}

	/**
	 * Retrieve all records.
	 *
	 * @param array|object|null $filter Filter criteria.
	 * @param int|null $offset Offset.
	 * @param int|null $count Count.
	 * @param array|null $modifiers Modifiers.
	 * @return object
	 */
	public function all(mixed $filter = null, ?int $offset = null, ?int $count = null, ?array $modifiers = null): object
	{
		$result = $this->modelClass::all($filter, $offset, $count, $modifiers);
		return $this->response($result);
	}

	/**
	 * Searches for models.
	 *
	 * @param mixed $search The search term.
	 * @return object The response.
	 */
	public function search(mixed $search): object
	{
		return $this->response(['rows' => $this->modelClass::search($search)]);
	}

	/**
	 * Retrieves the model row count.
	 *
	 * @return object The response.
	 */
	public function count(): object
	{
		return $this->response($this->modelClass::count());
	}
}