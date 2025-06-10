<?php declare(strict_types=1);
namespace Proto\Controllers;

use Proto\Http\Router\Request;
use Proto\Utils\Format\JsonFormat;
use Proto\Api\Validator;
use Proto\Models\Model;

/**
 * ResourceController
 *
 * This abstract class provides a base implementation for resource controllers.
 *
 * @package Proto\Controllers
 * @abstract
 */
abstract class ResourceController extends Controller
{
	use ModelTrait;

	/**
	 * The item key used in requests.
	 *
	 * @var string
	 */
	protected string $item = 'item';

	/**
	 * Initializes the resource controller.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setModelClass();
	}

	/**
	 * Retrieves the request item from the request object.
	 *
	 * @param Request $request The request object.
	 * @return object The request item.
	 */
	public function getRequestItem(Request $request): object
	{
		return $request->json($this->item) ?? (object) $request->all();
	}

	/**
	 * Validates the request data.
	 *
	 * This method can be overridden in subclasses to provide specific validation logic.
	 *
	 * @return array An array of validation errors, if any.
	 */
	protected function validate(): array
	{
		return [];
	}

	/**
	 * Validates the item data using the defined validation rules.
	 *
	 * @param object $item The item to validate.
	 * @param bool $isUpdating Whether the request is for updating an existing item.
	 * @return object The response object.
	 */
	public function validateItem(object $item, bool $isUpdating = false): bool
	{
		$rules = $this->validate();
		if (count($rules) < 1)
		{
			return true;
		}

		if ($isUpdating && !isset($item->id))
		{
			$idKeyName = $this->model::idKeyName();
			$rules[] = "{$idKeyName}|required";
		}

		return $this->validateRules($item, $rules);
	}

	/**
	 * Validates the request data.
	 *
	 * @param object|array $data The data to validate.
	 * @param array $rules The validation rules to apply.
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateRules(object|array $data, array $rules = []): bool
	{
		if (count($rules) < 1)
		{
			return true;
		}

		$validator = Validator::create($data, $rules);
		if (!$validator->isValid())
		{
			$this->errorValidating($validator);
			return false;
		}

		return true;
	}

	/**
	 * Handles validation errors by encoding the error message and rendering it as JSON.
	 *
	 * @param Validator $validator The validator object containing the error message.
	 * @return void
	 */
	protected function errorValidating(Validator $validator): void
    {
		$error = $this->error($validator->getMessage());
        JsonFormat::encodeAndRender($error);
        die;
    }

	/**
	 * Sets up model data.
	 *
	 * @param Request $request The request object.
	 * @return object The response.
	 */
	public function setup(Request $request): object
	{
		$data = $this->getRequestItem($request);
		if (empty($data))
		{
			return $this->error('No item provided.');
		}

		if (!$this->validateItem($data, false))
		{
			return $this->error('Invalid item data.');
		}

		$model = $this->model($data);
		$this->getAddUserData($model);
		$this->getUpdateUserData($model);

		return $model->setup()
			? $this->response(['id' => $model->id])
			: $this->error('Unable to add the item.');
	}

	/**
	 * Adds user data to the model.
	 *
	 * This method sets the `createdBy` and `authorId` fields to the current user's ID if they are not already set.
	 *
	 * @param Model $model The model instance to which user data will be added.
	 * @return void
	 */
	protected function getAddUserData(Model $model): void
	{
		if ($model->has('createdBy') && !is_numeric($model->createdBy))
		{
			$model->createdBy = session()->user->id ?? null;
		}

		if ($model->has('authorId') && !is_numeric($model->authorId))
		{
			$model->authorId = session()->user->id ?? null;
		}
	}

	/**
	 * Adds a model entry.
	 *
	 * @param Request $request The request object.
	 * @return object The response.
	 */
	public function add(Request $request): object
	{
		$data = $this->getRequestItem($request);
		if (empty($data))
		{
			return $this->error('No item provided.');
		}

		if (!$this->validateItem($data, false))
		{
			return $this->error('Invalid item data.');
		}

		$model = $this->model($data);
		$this->getAddUserData($model);

		return $model->add()
			? $this->response(['id' => $model->id])
			: $this->error('Unable to add the item.');
	}

	/**
	 * Adds user data to the model for updates.
	 *
	 * This method sets the `updatedBy` field to the current user's ID if it is not already set.
	 *
	 * @param Model $model The model instance to which user data will be added.
	 * @return void
	 */
	protected function getUpdateUserData(Model $model): void
	{
		if ($model->has('updatedBy') && !is_numeric($model->updatedBy))
		{
			$model->updatedBy = session()->user->id ?? null;
		}
	}

	/**
	 * Merges model data.
	 *
	 * @param Request $request The request object.
	 * @return object The response.
	 */
	public function merge(Request $request): object
	{
		$data = $this->getRequestItem($request);
		if (empty($data))
		{
			return $this->error('No item provided.');
		}

		if (!$this->validateItem($data, false))
		{
			return $this->error('Invalid item data.');
		}

		$model = $this->model($data);
		$this->getAddUserData($model);
		$this->getUpdateUserData($model);

		return $model->merge()
			? $this->response(['id' => $model->id])
			: $this->error('Unable to merge the item.');
	}

	/**
	 * Retrieves the resource ID from the request.
	 *
	 * @param Request $request The request object.
	 * @return int|null The resource ID or null if not found.
	 */
	protected function getResourceId(Request $request): ?int
	{
		$id = $request->getInt('id') ?? $request->params()->id ?? null;
		return (isset($id) && is_numeric($id)) ? (int) $id : null;
	}

	/**
	 * Updates model item status.
	 *
	 * @param Request $request The request object.
	 * @return object The response.
	 */
	public function updateStatus(Request $request): object
	{
		$id = $this->getResourceId($request);
		$status = $request->input('status') ?? null;
		if ($id === null || $status === null)
		{
			return $this->error('The ID and status are required.');
		}

		$model = $this->model((object) [
			'id' => $id,
			'status' => $status
		]);

		$this->getUpdateUserData($model);

		return $this->response(
			$model->updateStatus()
		);
	}

	/**
	 * Updates model data.
	 *
	 * @param Request $request The request object.
	 * @return object The response.
	 */
	public function update(Request $request): object
	{
		$data = $this->getRequestItem($request);
		if (empty($data))
		{
			return $this->error('No item provided.');
		}

		if (!$this->validateItem($data, true))
		{
			return $this->error('Invalid item data.');
		}

		$data->id = $data->id ?? $this->getResourceId($request);
		$model = $this->model($data);
		$this->getUpdateUserData($model);

		return $this->response(
			$model->update()
		);
	}

	/**
	 * Deletes model data.
	 *
	 * @param Request $request The request object.
	 * @return object The response.
	 */
	public function delete(Request $request): object
	{
		$id = $this->getResourceId($request);
		if ($id === null)
		{
			$data = $this->getRequestItem($request);
			if (empty($data))
			{
				return $this->error('No item provided.');
			}
			$id = $data->id ?? null;
		}

		if ($id === null)
		{
			return $this->error('The ID is required.');
		}

		$data = $this->getRequestItem($request);
		if (empty($data))
		{
			return $this->error('No item provided.');
		}

		$model = $this->model((object) ['id' => $id]);
		if ($model->has('deletedBy') && !is_numeric($model->deletedBy))
		{
			$model->deletedBy = session()->user->id ?? null;
		}

		return $this->response(
			$model->delete()
		);
	}

	/**
	 * Retrieves a model by ID.
	 *
	 * @param Request $request The request object.
	 * @return object The response.
	 */
	public function get(Request $request): object
	{
		$id = $this->getResourceId($request);
		if ($id === null)
		{
			return $this->error('The ID is required.');
		}

		return $this->response(['row' => $this->model::get($id)]);
	}

	/**
	 * This will get the filter from the request.
	 *
	 * @param Request $request The request object.
	 * @return mixed The filter criteria.
	 */
	public function getFilter(Request $request): mixed
	{
		$filter = $request->input('filter') ?? $request->input('option');
		if (is_string($filter))
		{
			$filter = urldecode($filter);
		}

		return JsonFormat::decode($filter) ?? (object)[];
	}

	/**
	 * Retrieve all records.
	 *
	 * @param array|object|null $filter Filter criteria.
	 * @param int|null $offset Offset.
	 * @param int|null $limit Count.
	 * @param array|null $modifiers Modifiers.
	 * @return object
	 */
	public function all(Request $request): object
	{
		$filter = $this->getFilter($request);
		$offset = $request->getInt('offset') ?? 0;
		$limit = $request->getInt('limit') ?? 50;
		$search = $request->input('search');
		$custom = $request->input('custom');

		$result = $this->model::all($filter, $offset, $limit, [
			'search' => $search,
			'custom' => $custom
		]);
		return $this->response($result);
	}

	/**
	 * Searches for models.
	 *
	 * @param Request $request The request object.
	 * @return object The response.
	 */
	public function search(Request $request): object
	{
		$search = $request->input('search');
		if (empty($search))
		{
			return $this->error('No search term provided.');
		}

		return $this->response(['rows' => $this->model::search($search)]);
	}

	/**
	 * Retrieves the model row count.
	 *
	 * @param Request $request The request object.
	 * @return object The response.
	 */
	public function count(Request $request): object
	{
		return $this->response($this->model::count());
	}
}