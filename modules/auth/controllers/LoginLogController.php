<?php declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Proto\Controllers\ResourceController as Controller;
use Modules\Auth\Models\LoginLog;
use Proto\Http\Router\Request;

/**
 * LoginLogController
 *
 * @package Modules\Auth\Controllers
 */
class LoginLogController extends Controller
{
	/**
	 * Initializes the model class.
	 *
	 * @param string|null $model The model class reference using ::class.
	 */
	public function __construct(protected ?string $model = LoginLog::class)
	{
		parent::__construct();
	}

	/**
	 * Modifies the filter object based on the request.
	 *
	 * @param mixed $filter
	 * @param Request $request
	 * @return object|null
	 */
	protected function modifyFilter(?object $filter, Request $request): ?object
	{
		$userId = $request->params()->userId ?? null;
		if (isset($userId))
		{
			$filter->userId = $userId;
		}

		return $filter;
	}
}