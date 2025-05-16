<?php declare(strict_types=1);
namespace Modules\Developer\Controllers;

use Proto\Error\Models\ErrorLog;

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
	 * This will get rows from a model.
	 *
	 * @param mixed $filter
	 * @param int $offset
	 * @param int $count
	 * @param array|null $modifiers
	 * @return object
	 */
	public function all(
        $filter = null,
        ?int $offset = null,
        ?int $count = null,
        ?array $modifiers = null
    ): object
	{
		$result = ErrorLog::all($filter, $offset, $count, $modifiers);
		return $this->response($result);
	}
}