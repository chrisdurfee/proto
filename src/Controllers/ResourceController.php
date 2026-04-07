<?php declare(strict_types=1);
namespace Proto\Controllers;

use Proto\Http\Router\Request;
use Proto\Models\Model;
use Proto\Services\ServiceResult;

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
	 * When true, automatically adds the session user's ID to the filter
	 * in all() queries and injects userId on add operations.
	 *
	 * @var bool
	 */
	protected bool $scopeToUser = false;

	/**
	 * The field name used for user scoping.
	 *
	 * @var string
	 */
	protected string $userScopeField = 'userId';

	/**
	 * Optional service class for delegating add/update/delete operations.
	 *
	 * When set, the service is auto-instantiated and addItem/updateItem/deleteItem
	 * delegate to the service's add/update/delete methods if they exist.
	 *
	 * @var string|null
	 */
	protected ?string $serviceClass = null;

	/**
	 * The service instance, auto-instantiated from $serviceClass.
	 *
	 * @var object|null
	 */
	protected ?object $service = null;

	/**
	 * Initializes the resource controller.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setModelClass();
		$this->initializeService();
	}

	/**
	 * Initializes the service instance from the $serviceClass property.
	 *
	 * Override this method to provide custom service instantiation logic
	 * (e.g., constructor arguments, dependency injection).
	 *
	 * @return void
	 */
	protected function initializeService(): void
	{
		if ($this->serviceClass !== null)
		{
			$this->service = new $this->serviceClass();
		}
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
		$userId = session()->user->id ?? null;
		if ($model->has('createdBy') && !isset($model->createdBy))
		{
			$model->createdBy = $userId;
		}

		if ($model->has('authorId') && !isset($model->authorId))
		{
			$model->authorId = $userId;
		}

		if ($model->has('userId') && !isset($model->userId))
		{
			$model->userId = $userId;
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

		$this->modifyAddItem($data, $request);
		if (!$this->validateItem($data, false))
		{
			return $this->error('Invalid item data.');
		}

		return $this->addItem($data);
	}

	/**
	 * Modifies a model entry before adding.
	 *
	 * When $scopeToUser is enabled, automatically injects the session
	 * user's ID into the data using the configured $userScopeField.
	 *
	 * @param object &$data The data to modify.
	 * @param Request $request The request object.
	 * @return void
	 */
	protected function modifyAddItem(object &$data, Request $request): void
	{
		if ($this->scopeToUser)
		{
			$field = $this->userScopeField;
			if (!isset($data->$field))
			{
				$data->$field = (int)(session()->user->id ?? 0);
			}
		}
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
		if ($this->service !== null && method_exists($this->service, 'add'))
		{
			$this->injectAuditData($data, ['createdBy', 'authorId', 'userId']);
			return $this->serviceResponse(
				$this->service->add($data),
				'Unable to add the item.'
			);
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
		$userId = session()->user->id ?? null;
		if ($model->has('updatedBy') && !isset($model->updatedBy))
		{
			$model->updatedBy = $userId;
		}

		if ($model->has('editedBy') && !isset($model->editedBy))
		{
			$model->editedBy = $userId;
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

		$data->id = $data->id ?? $this->getResourceId($request);
		$this->modifyUpdateItem($data, $request);
		if (!$this->validateItem($data, true))
		{
			return $this->error('Invalid item data.');
		}

		return $this->updateItem($data);
	}

	/**
	 * Modifies a model entry before updating.
	 *
	 * Automatically restricts immutable fields defined on the model
	 * to prevent them from being modified after creation.
	 *
	 * @param object &$data The data to modify.
	 * @param Request $request The request object.
	 * @return void
	 */
	protected function modifyUpdateItem(object &$data, Request $request): void
	{
		$immutableFields = $this->model::immutableFields();
		if (count($immutableFields) > 0)
		{
			$id = $data->id ?? null;
			$this->restrictFields($data, $immutableFields);
			if ($id !== null)
			{
				$data->id = $id;
			}
		}
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
		if ($this->service !== null && method_exists($this->service, 'update'))
		{
			$this->injectAuditData($data, ['updatedBy', 'editedBy']);
			return $this->serviceResponse(
				$this->service->update($data),
				'Unable to update the item.'
			);
		}

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
			return $this->error('The ID is required to delete.');
		}

		return $this->deleteItem((object) ['id' => $id]);
	}

	/**
	 * Adds user data to the model for deletions.
	 *
	 * This method sets the `deletedBy` field to the current user's ID if it is not already set.
	 *
	 * @param Model $model The model instance to which user data will be added.
	 * @return void
	 */
	protected function getDeleteUserData(Model $model): void
	{
		$userId = session()->user->id ?? null;
		if ($model->has('deletedBy') && !isset($model->deletedBy))
		{
			$model->deletedBy = $userId;
		}

		if ($model->has('removedBy') && !isset($model->removedBy))
		{
			$model->removedBy = $userId;
		}

		if ($model->has('archivedBy') && !isset($model->archivedBy))
		{
			$model->archivedBy = $userId;
		}
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
		if ($this->service !== null && method_exists($this->service, 'delete'))
		{
			$this->injectAuditData($data, ['deletedBy', 'removedBy', 'archivedBy']);
			return $this->serviceResponse(
				$this->service->delete($data),
				'Unable to delete the item.'
			);
		}

		$model = $this->model($data);
		$this->getDeleteUserData($model);

		return $model->delete()
			? $this->response(['id' => $model->id])
			: $this->error('Unable to delete the item.');
	}

	/**
	 * Injects audit fields into a plain data object before service delegation.
	 *
	 * Sets the current session user's ID on each field that is not already set.
	 * This provides the same audit data injection that getAddUserData/getUpdateUserData
	 * perform on Model instances, but for plain objects passed to services.
	 *
	 * @param object &$data The data object to inject audit fields into.
	 * @param array $fields The audit field names to inject.
	 * @return void
	 */
	protected function injectAuditData(object &$data, array $fields): void
	{
		$userId = session()->user->id ?? null;
		if ($userId === null)
		{
			return;
		}

		foreach ($fields as $field)
		{
			if (!isset($data->$field))
			{
				$data->$field = $userId;
			}
		}
	}

	/**
	 * Processes a service method's return value into a controller response.
	 *
	 * Handles ServiceResult objects, false for failures, and raw data for success.
	 * - ServiceResult: uses success/error from the result
	 * - false: returns the default error message
	 * - array/object: wraps in a success response
	 * - scalar (e.g., an ID): wraps as ['id' => $result]
	 *
	 * @param mixed $result The service method return value.
	 * @param string $errorMessage Default error message if the result indicates failure.
	 * @return object The response object.
	 */
	protected function serviceResponse(mixed $result, string $errorMessage = 'Operation failed.'): object
	{
		if ($result instanceof ServiceResult)
		{
			return $result->success
				? $this->response($result->data)
				: $this->error($result->error ?? $errorMessage);
		}

		if ($result === false)
		{
			return $this->error($errorMessage);
		}

		if (is_array($result) || is_object($result))
		{
			return $this->response($result);
		}

		return $this->response(['id' => $result]);
	}

	/**
	 * Validate and store an uploaded file, returning the new filename.
	 *
	 * @param Request $request The request object.
	 * @param string $fieldName The form field name for the file input.
	 * @param string $disk The storage disk (e.g., 'local', 's3').
	 * @param string $directory The subdirectory within the disk.
	 * @param string $rules Validation rules (e.g., 'image:2048|mimes:jpeg,png').
	 * @return string|null New filename, or null if no file uploaded.
	 */
	protected function handleFileUpload(
		Request $request,
		string $fieldName,
		string $disk = 'local',
		string $directory = '',
		string $rules = 'image:2048'
	): ?string
	{
		$file = $request->file($fieldName);
		if (!$file)
		{
			return null;
		}

		$this->validateRules([$fieldName => $file], [$fieldName => $rules]);
		$file->store($disk, $directory);

		return $file->getNewName();
	}

	/**
	 * Retrieves a model by ID.
	 *
	 * Calls enrichRow() after fetching so subclasses can append flags or
	 * related data without overriding the full get() method.
	 *
	 * @param Request $request The request object.
	 * @return object The response.
	 */
	public function get(Request $request): object
	{
		$id = $this->getResourceId($request);
		if ($id === null)
		{
			return $this->error('The ID is required to get the item.');
		}

		$model = $this->model::get($id);
		if ($model === null)
		{
			return $this->response(['row' => null]);
		}

		$row = $model->getData();
		$this->enrichRow($row, $request);
		return $this->response(['row' => $row]);
	}

	/**
	 * Hook called after a single row is fetched in get().
	 *
	 * Override to append computed properties, user-specific flags, or
	 * related data without needing to duplicate the full get() logic.
	 *
	 * @param object $row The formatted row data (plain object).
	 * @param Request $request The request object.
	 * @return void
	 */
	protected function enrichRow(object &$row, Request $request): void {}

	/**
	 * Retrieve all records.
	 *
	 * Calls enrichRows() after fetching so subclasses can append flags or
	 * related data in a single batch without overriding the full all() method.
	 *
	 * @param Request $request The request object.
	 * @return object
	 */
	public function all(Request $request): object
	{
		$inputs = $this->getAllInputs($request);
		$result = $this->model::all($inputs->filter, $inputs->offset, $inputs->limit, $inputs->modifiers);
		if ($result !== false && !empty($result->rows))
		{
			$this->enrichRows($result->rows, $request);
		}

		return $this->response($result ? (array) $result : false);
	}

	/**
	 * Hook called after multiple rows are fetched in all().
	 *
	 * Override to batch-append computed properties or related data.
	 * Always use a single IN-query per related table rather than per-row
	 * lookups to avoid N+1 queries.
	 *
	 * @param array $rows The formatted rows (plain objects).
	 * @param Request $request The request object.
	 * @return void
	 */
	protected function enrichRows(array &$rows, Request $request): void {}

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
		$count = $this->model::count();
		return $this->response($count ? (array) $count : false);
	}
}