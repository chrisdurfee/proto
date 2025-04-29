<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Proto\Controllers\ModelController as Controller;
use Modules\User\Models\RolePermission;

/**
 * RolePermissionController
 *
 * This controller handles CRUD operations for the RolePermission model.
 *
 * @package Modules\User\Controllers
 */
class RolePermissionController extends Controller
{
	/**
	 * @var string|null $policy
	 */
	protected ?string $policy = RolePermission::class;

	/**
	 * Initializes the model class.
	 *
	 * @param string|null $modelClass The model class reference using ::class.
	 */
	public function __construct(protected ?string $modelClass = RolePermission::class)
	{
		parent::__construct();
	}

	/**
	 * Deletes model data.
	 *
	 * @param int|object $data The model ID or object.
	 * @return object The response.
	 */
	public function delete(int|object $data): object
	{
		return $this->response(
			$this->model()->deleteRolePermission($data->roleId, $data->permissionId)
		);
	}
}