<?php declare(strict_types=1);
namespace Proto\Controllers;

use Proto\Http\Router\Request;
use Proto\Models\Model;

/**
 * ResourceController
 *
 * This abstract class provides a base implementation for resource controllers.
 *
 * @package Proto\Controllers
 * @abstract
 */
abstract class ResourceController extends ApiController
{
	use ModelTrait;

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

		return $this->setupItem($data);
	}

	/**
	 * Sets up a model item.
	 *
	 * This method initializes the model with the provided data and adds user data for creation and updates.
	 *
	 * @param object $data The data to set up the model with.
	 * @return object The response object.
	 */
	protected function setupItem(object $data): object
	{
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
		if ($model->has('createdBy') && !isset($model->createdBy))
		{
			$model->createdBy = session()->user->id ?? null;
		}

		if ($model->has('authorId') && !isset($model->authorId))
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

		return $this->addItem($data);
	}

	/**
	 * Adds a model item.
	 *
	 * This method initializes the model with the provided data and adds user data for creation and updates.
	 *
	 * @param object $data The data to set up the model with.
	 * @return object The response object.
	 */
	protected function addItem(object $data): object
	{
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
		if ($model->has('updatedBy') && !isset($model->updatedBy))
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

		return $this->mergeItem($data);
	}

	/**
	 * Merges a model item.
	 *
	 * This method initializes the model with the provided data and adds user data for creation and updates.
	 *
	 * @param object $data The data to set up the model with.
	 * @return object The response object.
	 */
	protected function mergeItem(object $data): object
	{
		$model = $this->model($data);
		$this->getAddUserData($model);
		$this->getUpdateUserData($model);

		return $model->merge()
			? $this->response(['id' => $model->id])
			: $this->error('Unable to merge the item.');
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

		return $this->updateItemStatus((object) [
			'id' => $id,
			'status' => $status
		]);
	}

	/**
	 * Updates the status of a model item.
	 *
	 * This method initializes the model with the provided data and adds user data for updates.
	 *
	 * @param object $data The data to set up the model with.
	 * @return object The response object.
	 */
	protected function updateItemStatus(object $data): object
	{
		$model = $this->model($data);
		$this->getUpdateUserData($model);

		return $model->updateStatus()
			? $this->response(['id' => $model->id])
			: $this->error('Unable to update the item status.');
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
		return $this->updateItem($data);
	}

	/**
	 * Updates a model item.
	 *
	 * This method initializes the model with the provided data and adds user data for updates.
	 *
	 * @param object $data The data to set up the model with.
	 * @return object The response object.
	 */
	protected function updateItem(object $data): object
	{
		$model = $this->model($data);
		$this->getUpdateUserData($model);

		return $model->update()
			? $this->response(['id' => $model->id])
			: $this->error('Unable to update the item.');
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

		return $this->deleteItem((object) ['id' => $id]);
	}

	/**
	 * Deletes a model item.
	 *
	 * This method initializes the model with the provided data and adds user data for deletion.
	 *
	 * @param object $data The data to set up the model with.
	 * @return object The response object.
	 */
	protected function deleteItem(object $data): object
	{
		$model = $this->model($data);
		if ($model->has('deletedBy') && !isset($model->deletedBy))
		{
			$model->deletedBy = session()->user->id ?? null;
		}

		return $model->delete()
			? $this->response(['id' => $model->id])
			: $this->error('Unable to delete the item.');
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
		$lastCursor = $request->input('lastCursor') ?? null;
		$dates = $this->setDateModifier($request);
		$orderBy = $this->setOrderByModifier($request);
		$groupBy = $this->setGroupByModifier($request);
		$since = $request->input('since') ?? null;

		$result = $this->model::all($filter, $offset, $limit, [
			'search' => $search,
			'custom' => $custom,
			'dates' => $dates,
			'orderBy' => $orderBy,
			'groupBy' => $groupBy,
			'cursor' => $lastCursor,
			'since' => $since
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