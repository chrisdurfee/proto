<?php declare(strict_types=1);
namespace Proto\Controllers;

use Proto\Controllers\Traits\AuditFieldsTrait;
use Proto\Controllers\Traits\BatchEnrichmentTrait;
use Proto\Controllers\Traits\FileUploadTrait;
use Proto\Controllers\Traits\ImageOptimizationTrait;
use Proto\Controllers\Traits\UserEnrichmentTrait;
use Proto\Http\Router\Request;
use Proto\Services\ServiceResult;
use Proto\Storage\Filter;

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
	use AuditFieldsTrait;
	use FileUploadTrait;
	use ImageOptimizationTrait;
	use UserEnrichmentTrait;
	use BatchEnrichmentTrait;

	/**
	 * When true, automatically adds the session user's ID to the filter
	 * in all() queries and overwrites userId on add/setup.
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
	 * Route parameters to auto-inject on add and auto-filter on all().
	 * Keys are the route param name, values control behavior:
	 *   - true: required (setError if missing)
	 *   - false: optional (apply only if present)
	 *
	 * @var array<string, bool>
	 */
	protected array $routeParams = [];

	/**
	 * Query string parameters to auto-apply as filter conditions.
	 * Maps param name to type ('int', 'string', 'bool').
	 *
	 * @var array<string, string>
	 */
	protected array $filterParams = [];

	/**
	 * When true, unqualified filter keys that match the model $fields
	 * are prefixed with the model alias so joins do not make columns
	 * like `status` ambiguous.
	 *
	 * @var bool
	 */
	protected bool $qualifyFilters = true;

	/**
	 * Alternate keys accepted by get() when the route id is not numeric.
	 * `id` is always tried first via getResourceId().
	 *
	 * @var array<int, string>
	 */
	protected array $lookupKeys = ['id'];

	/**
	 * Declarative batch enrichments applied on get() and all() before
	 * enrichRows(). Each entry is keyed by the field set on the row.
	 *
	 * Types: `flag` (exists), `field` (copy a column), `count`.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected array $enrichments = [];

	/**
	 * Viewer-scoped boolean flags (liked, bookmarked, …). Same shape as
	 * CurrentUserFlagsTrait::$currentUserFlags. Applied automatically.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected array $currentUserFlags = [];

	/**
	 * Extra include names allowed on `?include=`. Unknown names are ignored.
	 *
	 * @var array<int, string>
	 */
	protected array $allowedIncludes = [];

	/**
	 * Includes always applied (still must be in $allowedIncludes to be
	 * request-overridable; defaults are merged in regardless).
	 *
	 * @var array<int, string>
	 */
	protected array $defaultIncludes = [];

	/**
	 * Controller-level Scope class-strings applied on all() in addition
	 * to the model's $scopes.
	 *
	 * @var array<int, class-string>
	 */
	protected array $scopes = [];

	/**
	 * When true, ModelPolicy shares list (`all`) cache keys across
	 * viewers after `applyListScopes()` is folded in. `get()` stays
	 * user/session scoped. Viewer flags are stripped before cache
	 * and re-applied after a list hit.
	 *
	 * @var bool
	 */
	protected bool $cacheSharedPayload = false;

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
	 * Qualify model-field filters after the standard filter modifiers run.
	 *
	 * @param Request $request
	 * @return mixed
	 */
	public function getFilter(Request $request): mixed
	{
		return $this->qualifyFilter(parent::getFilter($request));
	}

	/**
	 * Request filters may only predicate on filterable model fields.
	 *
	 * @return array<int, string>|null
	 */
	protected function requestFilterColumns(): ?array
	{
		if ($this->model === null)
		{
			return null;
		}

		if (is_callable([$this->model, 'filterableFields']))
		{
			return $this->model::filterableFields();
		}

		return is_callable([$this->model, 'fields']) ? $this->model::fields() : null;
	}

	/**
	 * Prefix unqualified model fields with the model alias.
	 *
	 * Controllers that build their own filter arrays should call this
	 * so join-safe lists do not need a hand-rolled alias map.
	 *
	 * @param mixed $filter
	 * @return mixed
	 */
	protected function qualifyFilter(mixed $filter): mixed
	{
		if (!$this->qualifyFilters || $this->model === null || !is_callable([$this->model, 'alias']))
		{
			return $filter;
		}

		$alias = $this->model::alias();
		if ($alias === null || $alias === '')
		{
			return $filter;
		}

		$fields = is_callable([$this->model, 'fields']) ? $this->model::fields() : [];
		return Filter::qualify($filter, $alias, $fields);
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

		if ($isUpdating)
		{
			$rules = $this->rulesForPartialUpdate($rules, $item);
			if (!isset($item->id))
			{
				$idKeyName = $this->model::idKeyName();
				$rules[] = "{$idKeyName}|required";
			}
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

		$this->modifyAddItem($data, $request);
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

		$response = $this->addItem($data);
		$this->dispatchLifecycle('afterAdd', $data, $request, $response);
		return $response;
	}

	/**
	 * Modifies a model entry before adding.
	 *
	 * When $scopeToUser is enabled, always overwrites the configured
	 * $userScopeField with the session user's ID so a client cannot
	 * set another user's id.
	 * When $routeParams is set, auto-injects those route parameters.
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
			$data->$field = (int)(session()->user->id ?? 0);
		}

		$this->applyRouteParamsToData($data, $request);
	}

	/**
	 * Injects route parameters into a data object.
	 *
	 * @param object &$data The data to modify.
	 * @param Request $request The request object.
	 * @return void
	 */
	protected function applyRouteParamsToData(object &$data, Request $request): void
	{
		if (empty($this->routeParams))
		{
			return;
		}

		$params = $request->params();
		foreach ($this->routeParams as $param => $required)
		{
			$value = (int)($params->$param ?? 0);
			if ($required && !$value)
			{
				$this->setError(ucfirst($param) . ' is required');
				return;
			}
			if ($value)
			{
				$data->$param = $value;
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
			$result = $this->service->add($data);
			$response = $this->serviceResponse($result, 'Unable to add the item.');
			$this->attachUserFields($response);
			return $response;
		}

		$model = $this->model($data);
		$this->getAddUserData($model);

		if (!$model->add())
		{
			return $this->error('Unable to add the item.');
		}

		$responseData = (object)['id' => $model->id];
		$this->attachUserFields($responseData);
		return $this->response((array)$responseData);
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

		$response = $this->updateItem($data);
		$this->dispatchLifecycle('afterUpdate', $data, $request, $response);
		return $response;
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
			$result = $this->service->update($data);
			$response = $this->serviceResponse($result, 'Unable to update the item.');
			$this->attachUserFields($response);
			return $response;
		}

		$model = $this->model($data);
		$this->getUpdateUserData($model);

		if (!$model->update())
		{
			return $this->error('Unable to update the item.');
		}

		$responseData = (object)['id' => $model->id];
		$this->attachUserFields($responseData);
		return $this->response((array)$responseData);
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

		$item = (object) ['id' => $id];
		$response = $this->deleteItem($item);
		$this->dispatchLifecycle('afterDelete', $item, $request, $response);
		return $response;
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
		$hasAlternate = $this->hasAlternateLookupKeys();
		if ($id === null && !$hasAlternate)
		{
			return $this->error('The ID is required to get the item.');
		}

		$includes = $this->requestedIncludes($request);
		if ($includes !== [] && $this->model !== null && is_callable([$this->model, 'setRequestedIncludes']))
		{
			$this->model::setRequestedIncludes($includes);
		}

		try
		{
			$model = $this->resolveGetModel($request);
			if ($model === null)
			{
				return $this->response(['row' => null]);
			}

			$row = method_exists($model, 'getData') ? $model->getData() : $model;
			$this->enrichRow($row, $request);
			return $this->response(['row' => $row]);
		}
		finally
		{
			if ($this->model !== null && is_callable([$this->model, 'setRequestedIncludes']))
			{
				$this->model::setRequestedIncludes([]);
			}
		}
	}

	/**
	 * Hook called after a single row is fetched in get().
	 *
	 * By default, delegates to enrichRows() so subclasses only need to
	 * implement the batch version. Override individually only if the
	 * single-item path genuinely needs different logic.
	 *
	 * @param object $row The formatted row data (plain object).
	 * @param Request $request The request object.
	 * @return void
	 */
	protected function enrichRow(object &$row, Request $request): void
	{
		$rows = [&$row];
		$this->applyDeclaredEnrichments($rows, $request);
		$this->enrichRows($rows, $request);
	}

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
		$filter = $this->applyListScopes($inputs->filter, $request);
		$includes = $this->requestedIncludes($request);
		if ($includes !== [])
		{
			$inputs->modifiers['include'] = $includes;
		}

		$inputs->modifiers['scopesApplied'] = true;
		$result = $this->model::all($filter, $inputs->offset, $inputs->limit, $inputs->modifiers);
		if ($result !== false && !empty($result->rows))
		{
			$this->applyDeclaredEnrichments($result->rows, $request);
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

		$filter = $this->applyListScopes($this->getFilter($request), $request);
		$modifiers = [
			'search' => $search,
			'scopesApplied' => true
		];
		$result = $this->model::all($filter, 0, $this->maxLimit, $modifiers);
		if ($result !== false && !empty($result->rows))
		{
			$this->applyDeclaredEnrichments($result->rows, $request);
			$this->enrichRows($result->rows, $request);
		}

		return $this->response($result ? (array) $result : false);
	}

	/**
	 * Retrieves the model row count.
	 *
	 * @param Request $request The request object.
	 * @return object The response.
	 */
	public function count(Request $request): object
	{
		$inputs = $this->getAllInputs($request);
		$filter = $this->applyListScopes($inputs->filter, $request);
		$modifiers = $inputs->modifiers ?? [];
		$modifiers['scopesApplied'] = true;
		$count = $this->model::count($filter, $modifiers);
		return $this->response($count ? (array) $count : false);
	}

	/**
	 * Whether get() should accept non-numeric lookup keys (uuid, slug).
	 *
	 * @return bool
	 */
	protected function hasAlternateLookupKeys(): bool
	{
		foreach ($this->lookupKeys as $key)
		{
			if ($key !== 'id')
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Resolve a model for get() by numeric id or $lookupKeys (uuid/slug).
	 *
	 * Applies the same list scopes as all() so drafts / private rows
	 * are not readable by id unless the scope allows the actor.
	 *
	 * @param Request $request
	 * @return object|null
	 */
	protected function resolveGetModel(Request $request): ?object
	{
		$id = $this->getResourceId($request);
		if ($id !== null)
		{
			return $this->firstScoped($request, ['id' => $id]);
		}

		$raw = $request->input('id') ?? $request->params()->id ?? null;
		if (!is_string($raw) || $raw === '')
		{
			return null;
		}

		foreach ($this->lookupKeys as $key)
		{
			if ($key === 'id')
			{
				continue;
			}

			$found = $this->firstScoped($request, [$key => $raw]);
			if ($found !== null)
			{
				return $found;
			}
		}

		return null;
	}

	/**
	 * First row matching a lookup after list scopes are applied.
	 *
	 * Lookup keys (`id`, slug, uuid) are always alias-qualified so a
	 * User (or any `id`) join cannot make `WHERE id = ?` ambiguous.
	 * This runs even when `$qualifyFilters` is false (list filters are
	 * already aliased by the app).
	 *
	 * @param Request $request
	 * @param array $lookup
	 * @return object|null
	 */
	protected function firstScoped(Request $request, array $lookup): ?object
	{
		if ($this->model === null)
		{
			return null;
		}

		$filter = $this->qualifyLookupKeys(
			$this->applyListScopes($lookup, $request),
			array_keys($lookup)
		);
		$filter = $this->qualifyFilter($filter);
		$result = $this->model::all($filter, 0, 1, ['scopesApplied' => true]);
		if ($result === false || empty($result->rows))
		{
			return null;
		}

		return $result->rows[0];
	}

	/**
	 * Prefix lookup columns with the model alias.
	 *
	 * Independent of `$qualifyFilters` so get() stays join-safe when
	 * list endpoints disable auto-qualify to avoid double-aliasing.
	 *
	 * @param mixed $filter
	 * @param array<int, int|string> $keys
	 * @return mixed
	 */
	protected function qualifyLookupKeys(mixed $filter, array $keys): mixed
	{
		if ($this->model === null || !is_callable([$this->model, 'alias']))
		{
			return $filter;
		}

		$alias = $this->model::alias();
		if ($alias === null || $alias === '')
		{
			return $filter;
		}

		$columns = [];
		foreach ($keys as $key)
		{
			if (is_string($key) && $key !== '')
			{
				$columns[] = $key;
			}
		}

		if ($columns === [])
		{
			return $filter;
		}

		return Filter::qualify($filter, $alias, $columns);
	}

	/**
	 * Strip `required` from rules for fields omitted on PATCH.
	 *
	 * @param array $rules
	 * @param object $item
	 * @return array
	 */
	protected function rulesForPartialUpdate(array $rules, object $item): array
	{
		$out = [];
		foreach ($rules as $key => $rule)
		{
			if (is_int($key))
			{
				$out[] = $rule;
				continue;
			}

			if (!isset($item->$key) && is_string($rule))
			{
				$parts = array_values(array_filter(
					explode('|', $rule),
					fn(string $part): bool => strtolower($part) !== 'required'
				));
				$rule = implode('|', $parts);
			}

			$out[$key] = $rule;
		}

		return $out;
	}

	/**
	 * Apply $enrichments and $currentUserFlags with batch IN queries.
	 *
	 * Called automatically from get()/all() so subclasses do not need
	 * to call parent::enrichRows() to get declared flags.
	 *
	 * @param array $rows
	 * @param Request $request
	 * @return void
	 */
	protected function applyDeclaredEnrichments(array &$rows, Request $request): void
	{
		if (empty($rows))
		{
			return;
		}

		$userId = (int)(session()->user->id ?? 0);
		$userId = $userId > 0 ? $userId : null;

		foreach ($this->currentUserFlags as $field => $config)
		{
			$this->applyEnrichment($rows, (string)$field, array_merge($config, ['type' => 'flag']), $userId);
		}

		$includes = $this->requestedIncludes($request);
		foreach ($this->enrichments as $field => $config)
		{
			$includeKey = $config['include'] ?? null;
			if ($includeKey !== null && !in_array($includeKey, $includes, true))
			{
				continue;
			}

			$this->applyEnrichment($rows, (string)$field, $config, $userId);
		}
	}

	/**
	 * @param array $rows
	 * @param string $field
	 * @param array<string, mixed> $config
	 * @param int|null $userId
	 * @return void
	 */
	protected function applyEnrichment(array &$rows, string $field, array $config, ?int $userId): void
	{
		$type = $config['type'] ?? 'flag';
		$model = $config['model'] ?? null;
		$foreignKey = $config['foreignKey'] ?? null;
		if ($model === null || $foreignKey === null)
		{
			return;
		}

		$sourceKey = $config['sourceKey'] ?? 'id';
		$extraFilter = $config['extraFilter'] ?? [];

		if ($type === 'flag')
		{
			if ($userId === null)
			{
				foreach ($rows as &$row)
				{
					$row->$field = false;
				}
				unset($row);
				return;
			}

			$userField = $config['userField'] ?? 'userId';
			$extraFilter = array_merge([[$userField, $userId]], $extraFilter);
			$this->batchMapExists($rows, $model, $foreignKey, $field, $extraFilter, $sourceKey);
			return;
		}

		if ($type === 'count')
		{
			$this->batchMapCount($rows, $model, $foreignKey, $field, $extraFilter, $sourceKey);
			return;
		}

		if ($type === 'field')
		{
			$valueField = $config['valueField'] ?? $field;
			$default = $config['default'] ?? null;
			$this->batchMapField($rows, $model, $foreignKey, $valueField, $field, $default, $extraFilter, $sourceKey);
		}
	}

	/**
	 * Run a lifecycle hook after a successful mutation.
	 *
	 * @param string $hook afterAdd|afterUpdate|afterDelete
	 * @param object $data
	 * @param Request $request
	 * @param object $response
	 * @return void
	 */
	protected function dispatchLifecycle(string $hook, object $data, Request $request, object $response): void
	{
		if (!($response->success ?? false))
		{
			return;
		}

		if (!isset($data->id))
		{
			$data->id = $response->id ?? null;
		}

		$this->$hook($data, $request);
		$this->emit($this->lifecycleEvent($hook), $data);
	}

	/**
	 * Publish a domain event.
	 *
	 * @param string $event
	 * @param mixed $payload
	 * @return void
	 */
	protected function emit(string $event, mixed $payload = null): void
	{
		if (function_exists('events'))
		{
			events()->emit($event, $payload);
		}
	}

	/**
	 * @param string $hook
	 * @return string
	 */
	protected function lifecycleEvent(string $hook): string
	{
		$base = 'resource';
		if ($this->model !== null)
		{
			$short = (new \ReflectionClass($this->model))->getShortName();
			$base = lcfirst($short);
		}

		return match ($hook)
		{
			'afterAdd' => $base . '.created',
			'afterUpdate' => $base . '.updated',
			'afterDelete' => $base . '.deleted',
			default => $base . '.' . $hook
		};
	}

	/**
	 * Allowlisted includes from `?include=` plus defaults.
	 *
	 * @param Request $request
	 * @return array<int, string>
	 */
	public function requestedIncludes(Request $request): array
	{
		$raw = $request->input('include');
		$asked = [];
		if (is_string($raw) && $raw !== '')
		{
			$asked = array_values(array_filter(array_map('trim', explode(',', $raw))));
		}

		if ($this->allowedIncludes !== [])
		{
			$asked = array_values(array_intersect($asked, $this->allowedIncludes));
		}
		else
		{
			$asked = [];
		}

		return array_values(array_unique(array_merge($this->defaultIncludes, $asked)));
	}

	/**
	 * Apply model scopes, controller scopes, and Policy::scope().
	 *
	 * @param mixed $filter
	 * @param Request $request
	 * @return mixed
	 */
	public function applyListScopes(mixed $filter, Request $request): mixed
	{
		$actor = (function_exists('session')) ? (session()->user ?? null) : null;
		if ($this->model !== null && is_callable([$this->model, 'applyScopes']))
		{
			$filter = $this->model::applyScopes($filter, $actor);
		}

		foreach ($this->scopes as $scope)
		{
			$instance = is_string($scope) ? new $scope() : $scope;
			if ($instance instanceof \Proto\Models\Scopes\Scope)
			{
				$filter = $instance->apply($filter, $actor);
			}
		}

		if ($this->policy !== null && class_exists($this->policy))
		{
			$policy = new $this->policy($this);
			if (method_exists($policy, 'scope'))
			{
				$filter = $policy->scope($filter, $request);
			}
		}

		return $filter;
	}

	/**
	 * Re-apply declared enrichments after a shared cache hit.
	 *
	 * @param array $rows
	 * @param Request $request
	 * @return void
	 */
	public function reapplyEnrichments(array &$rows, Request $request): void
	{
		$this->applyDeclaredEnrichments($rows, $request);
	}

	/**
	 * Whether ModelPolicy should share list cache keys (no viewer flags).
	 *
	 * @return bool
	 */
	public function usesSharedCache(): bool
	{
		return $this->cacheSharedPayload;
	}

	/**
	 * Field names that are viewer-specific and must be stripped before
	 * storing a shared cache payload.
	 *
	 * @return array<int, string>
	 */
	public function viewerFlagFields(): array
	{
		$fields = array_keys($this->currentUserFlags);
		foreach ($this->enrichments as $field => $config)
		{
			if (($config['type'] ?? 'flag') === 'flag')
			{
				$fields[] = (string)$field;
			}
		}

		return array_values(array_unique($fields));
	}

	/**
	 * @param object $item
	 * @param Request $request
	 * @return void
	 */
	protected function afterAdd(object $item, Request $request): void {}

	/**
	 * @param object $item
	 * @param Request $request
	 * @return void
	 */
	protected function afterUpdate(object $item, Request $request): void {}

	/**
	 * @param object $item
	 * @param Request $request
	 * @return void
	 */
	protected function afterDelete(object $item, Request $request): void {}
}