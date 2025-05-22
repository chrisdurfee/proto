<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Modules\User\Auth\Policies\UserRolePolicy;
use Proto\Controllers\ResourceController as Controller;
use Modules\User\Models\UserRole;
use Proto\Http\Router\Request;

/**
 * UserRoleController
 *
 * This controller handles user role management.
 *
 * @package Modules\User\Controllers
 */
class UserRoleController extends Controller
{
	/**
	 * @var string|null $policy
	 */
	protected ?string $policy = UserRolePolicy::class;

	/**
	 * Initializes the model class.
	 *
	 * @param string|null $modelClass The model class reference using ::class.
	 */
	public function __construct(protected ?string $modelClass = UserRole::class)
	{
		parent::__construct();
	}

	/**
	 * Deletes model data.
	 *
	 * @param Request $request The request object.
	 * @return object The response.
	 */
	public function delete(Request $request): object
	{
		$data = $this->getRequestItem($request);
		if (empty($data) || empty($data->userId) || empty($data->roleId))
		{
			return $this->error('No item provided.');
		}

		return $this->response(
			$this->model()->deleteUserRole($data->userId, $data->roleId)
		);
	}
}