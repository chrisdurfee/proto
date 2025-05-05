<?php declare(strict_types=1);
namespace Proto\Controllers;

use Proto\Http\Router\Request;
use Proto\Utils\Format\JsonFormat;

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
		return $request->json('item') ?? (object) $request->all();
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

		$model = $this->model($data);
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

		$model = $this->model($data);
		return $model->add()
			? $this->response(['id' => $model->id])
			: $this->error('Unable to add the item.');
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

		$model = $this->model($data);
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
		$id = $request->getInt('id') ?? $request->params()->id ?? null;
		$status = $request->input('status') ?? null;
		if ($id === null || $status === null)
		{
			return $this->error('The ID and status are required.');
		}

		return $this->response(
			$this->model((object) [
				'id' => $id,
				'status' => $status
			])->updateStatus()
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

		return $this->response(
			$this->model($data)->update()
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
		$id = $request->getInt('id') ?? $request->params()->id ?? null;
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

		return $this->response(
			$this->model((object) ['id' => $id])->delete()
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
		$id = $request->getInt('id') ?? $request->params()->id ?? null;
		if ($id === null)
		{
			return $this->error('The ID is required.');
		}

		return $this->response(['row' => $this->modelClass::get($id)]);
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
	 * @param int|null $count Count.
	 * @param array|null $modifiers Modifiers.
	 * @return object
	 */
	public function all(Request $request): object
	{
		$filter = $this->getFilter($request);
		$offset = $request->getInt('start') ?? 0;
		$count = $request->getInt('count') ?? 50;
		$search = $request->input('search');

		$result = $this->modelClass::all($filter, $offset, $count, [
			'search' => $search
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

		return $this->response(['rows' => $this->modelClass::search($search)]);
	}

	/**
	 * Retrieves the model row count.
	 *
	 * @param Request $request The request object.
	 * @return object The response.
	 */
	public function count(Request $request): object
	{
		return $this->response($this->modelClass::count());
	}
}