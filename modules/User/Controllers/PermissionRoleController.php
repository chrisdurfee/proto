<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Modules\User\Auth\Policies\PermissionPolicy;
use Proto\Controllers\ResourceController as Controller;
use Modules\User\Models\PermissionRole;
use Proto\Http\Router\Request;

/**
 * PermissionRoleController
 *
 * This controller handles CRUD operations for the PermissionRole model.
 *
 * @package Modules\User\Controllers
 */
class PermissionRoleController extends Controller
{
	/**
	 * @var string|null $policy
	 */
	protected ?string $policy = PermissionPolicy::class;

	/**
	 * Initializes the model class.
	 *
	 * @param string|null $model The model class reference using ::class.
	 */
	public function __construct(protected ?string $model = PermissionRole::class)
	{
		parent::__construct();
	}

	/**
	 * Deletes model data.
	 *
	 * @param int|object $data The model ID or object.
	 * @return object The response.
	 */
	public function delete(Request $request): object
	{
		$data = $this->getRequestItem($request);
		if (empty($data) || empty($data->roleId) || empty($data->permissionId))
		{
			return $this->error('No item provided.');
		}

		return $this->response(
			$this->model()->deleteRolePermission($data->roleId, $data->permissionId)
		);
	}
}