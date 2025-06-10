<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Modules\User\Auth\Policies\RoleUserPolicy;
use Proto\Controllers\ResourceController as Controller;
use Modules\User\Models\RoleUser;
use Proto\Http\Router\Request;

/**
 * RoleUserController
 *
 * This controller handles role-user management.
 *
 * @package Modules\User\Controllers
 */
class RoleUserController extends Controller
{
	/**
	 * @var string|null $policy
	 */
	protected ?string $policy = RoleUserPolicy::class;

	/**
	 * Initializes the model class.
	 *
	 * @param string|null $model The model class reference using ::class.
	 */
	public function __construct(protected ?string $model = RoleUser::class)
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