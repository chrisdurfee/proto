<?php declare(strict_types=1);
namespace Proto\Controllers;

/**
 * ModelController
 *
 * This will create a base controller that is extend to
 * child classes to work with CRUD models.
 *
 * @package Proto\Controllers
 * @abstract
 */
abstract class ModelController extends Controller
{
	/**
	 * @var bool $passResponse
	 */
	protected $passResponse = false;

	/**
	 * This will setup the model class.
	 *
	 * @param string|null $modelClass by using the magic constant ::class
	 * @return void
	 */
	public function __construct(
		protected ?string $modelClass = null
	)
	{
		parent::__construct();
		$this->setModelClass();
	}

	/**
	 * This will get a model by class.
	 *
	 * @return string by using the magic constant ::class
	 */
    protected function getModelClass()
	{
		return $this->modelClass;
	}

	/**
	 * This will set the model class.
	 *
	 * @return void
	 */
	protected function setModelClass(): void
	{
		if (isset($this->modelClass))
		{
			return;
		}

		$modelClass = $this->getModelClass();
		if ($modelClass)
		{
			$this->modelClass = $this->getModelClass();
		}
	}

	/**
	 * This will get the model class.
	 *
	 * @return string|null
	 */
	public function getModelClassName(): ?string
	{
		return $this->modelClass;
	}

	/**
	 * This will get a new model.
	 *
	 * @param object|null $data
	 * @return object|null
	 */
    protected function model(?object $data = null): ?object
	{
		$modelClass = $this->modelClass;
		if (!$modelClass)
		{
			return null;
		}

		/**
		 * @var object $modelClass
		 */
		return new $modelClass($data);
	}

	/**
	 * This is an alias for model.
	 *
	 * @param object|null $data
	 * @return object|void
	 */
    protected function getModel(?object $data = null): ?object
	{
		return $this->model($data);
	}

	/**
	 * This will get the model storage.
	 *
	 * @param object|null $data
	 * @return object|null
	 */
    protected function storage(?object $data = null): ?object
	{
		$model = $this->model($data);
		if (!$model)
		{
			return null;
		}

		return $model->storage();
	}

	/**
	 * This will allow the model to be called directly without
	 * having to declare all methods in the controller.
	 *
	 * @param string $method
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call(string $method, array $arguments): mixed
    {
		$model = $this->model();
		$value = [$model, $method];
        if (!\is_callable($value))
        {
            return $this->error('The method is not callable.');
		}

		$result = \call_user_func_array($value, $arguments);

		if ($this->passResponse === false)
		{
			return $result;
		}

		if (is_bool($result) === true)
		{
			return $this->response($result);
		}

		if (is_array($result) === false)
		{
			return $this->response([
				'row' => $result
			]);
		}

		return $this->response([
			'rows' => $result
		]);
    }

	/**
	 * This will allow the storage to be called directly without
	 * being wrapped by the controller if the passResponse is set.
	 *
	 * @param string $method
	 * @param array $arguments
	 * @return mixed
	 */
	public static function __callStatic(string $method, array $arguments): mixed
	{
		$controller = new static();
		$modelClass = $controller->getModelClassName();
		$value = [$modelClass, $method];

		if (!\is_callable($value))
        {
            return Response::errorResponse('The method is not callable.');
		}

		return \call_user_func_array($value, $arguments);
	}

	/**
	 * This will setup model data.
	 *
	 * @param object $data
	 * @return object
	 */
	public function setup(object $data): object
	{
		$model = $this->model($data);
		$result = $model->setup();
		if ($result === false)
		{
			return $this->error('Unable to add the item.');
		}

		return $this->response((object)[
			'success' => true,
			'id' => $model->id
		]);
	}

	/**
	 * This will add or update an item.
	 *
	 * @param object $data
	 * @return object
	 */
	public static function put(object $data): object
	{
		$controller = new static();
		return $controller->setup($data);
	}

	/**
	 * This will add model data.
	 *
	 * @param object $data
	 * @return object
	 */
	public function add(object $data): object
	{
		$model = $this->model($data);
		$result = $model->add();
		if ($result === false)
		{
			return $this->error('Unable to add the item.');
		}

		return $this->response((object)[
			'success' => true,
			'id' => $model->id
		]);
	}

	/**
	 * This will add an item.
	 *
	 * @param object $data
	 * @return object
	 */
	public static function create(object $data): object
	{
		$controller = new static();
		return $controller->add($data);
	}

	/**
	 * This will add model data.
	 *
	 * @param object $data
	 * @return object
	 */
	public function merge(object $data): object
	{
		$model = $this->model($data);
		$result = $model->merge();
		if ($result === false)
		{
			return $this->error('Unable to merge the item.');
		}

		return $this->response((object)[
			'success' => true,
			'id' => $model->id
		]);
	}

	/**
	 * This will update model item status.
	 *
	 * @param int $id
	 * @param mixed $status
	 * @return object
	 */
	public function updateStatus(int $id, $status): object
	{
		$model = $this->model((object)[
			'id' => $id,
			'status' => $status
		]);
		$result = $model->updateStatus();
		return $this->response($result);
	}

	/**
	 * This will update model data.
	 *
	 * @param object $data
	 * @return object
	 */
	public function update(object $data): object
	{
		$result = $this->model($data)->update();
		return $this->response($result);
	}

	/**
	 * This will add an item.
	 *
	 * @param object $data
	 * @return object
	 */
	public static function edit(object $data): object
	{
		$controller = new static();
		return $controller->update($data);
	}

	/**
	 * This will delete model data.
	 *
	 * @param int|object $data
	 * @return object
	 */
	public function delete($data): object
	{
		$id = (gettype($data) === 'object')? $data->id : $data;

		$model = $this->model((object)[
			'id' => $id
		]);
		$result = $model->delete();
		return $this->response($result);
	}

	/**
	 * This will add an item.
	 *
	 * @param object $data
	 * @return object
	 */
	public static function remove(object $data): object
	{
		$controller = new static();
		return $controller->delete($data);
	}

	/**
	 * This will get model data.
	 *
	 * @param int $id
	 * @return object
	 */
	public function get(int $id): object
	{
		$result = $this->modelClass::get($id);

		return $this->response([
			'row' => $result
		]);
	}

	/**
	 * This will do a search.
	 *
	 * @param mixed $search
	 * @return object
	 */
	public function search($search): object
	{
		$result = $this->modelClass::search($search);

		return $this->response([
			'rows' => $result
		]);
	}

	/**
	 * This will get the row count from a model.
	 *
	 * @param mixed $filter
	 * @param array|null $modifiers
	 * @return object
	 */
	public function count($filter = null, ?array $modifiers = null): object
	{
		$result = $this->modelClass::count($filter, $modifiers);
		return $this->response($result);
	}

	/**
	 * This will get rows from a model.
	 *
	 * @param mixed $filter
	 * @param int|null $offset
	 * @param int|null $count
	 * @param array|null $modifiers
	 * @return object
	 */
	public function all($filter = null, ?int $offset = null, ?int $count = null, ?array $modifiers = null): object
	{
		$result = $this->modelClass::all($filter, $offset, $count, $modifiers);
		return $this->response($result);
	}
}