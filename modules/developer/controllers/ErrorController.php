<?php declare(strict_types=1);
namespace Modules\Developer\Controllers;

use Proto\Error\Models\ErrorLog;
use Proto\Http\Router\Request;

/**
 * ErrorController
 *
 * This will be the controller for the error.
 *
 * @package Modules\Developer\Controllers
 */
class ErrorController extends Controller
{
	/**
	 * This will toggle the resolved status of an error.
	 *
	 * @param Request $request
	 * @return object
	 */
	public function toggleResolve(Request $request): object
	{
		$id = $request->getInt('id');
		$resolved = $request->input('resolved');
		if (empty($id) || empty($resolved))
		{
			return $this->response()->error('Invalid request.');
		}

		return $this->updateResolved($id, $resolved);
	}
	/**
	 * This will update model item resolved status.
	 *
	 * @param int $id
	 * @param string|int $resolved
	 * @return object
	 */
	public function updateResolved(int $id, string|int $resolved): object
	{
		$model = new ErrorLog((object)[
			'id' => $id,
			'resolved' => $resolved
		]);
		$result = $model->updateResolved();
		return $this->response($result);
	}

	/**
	 * This will get the filter for the model.
	 *
	 * @param string|null $filter
	 * @return array
	 */
	protected function setFilter(?string $filter): array
	{
		$filter = strtolower($filter ?? '');
		if (empty($filter) || $filter === 'all')
		{
			return [];
		}

		return [
			['env', $filter]
		];
	}

    /**
	 * This will get rows from a model.
	 *
	 * @param Request $request
	 * @return object
	 */
	public function all(
        Request $request
    ): object
	{
		$filter = $request->input('filter');
		$filter = $this->setFilter($filter);

		$offset = $request->getInt('offset');
		$limit = $request->getInt('limit');
		$search = $request->input('search');
		$custom = $request->input('custom');

		$result = ErrorLog::all($filter, $offset, $limit, [
			'search' => $search,
			'custom' => $custom
		]);
		return $this->response($result);
	}
}