<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Proto\Controllers\ModelController as Controller;
use Modules\User\Models\UserRole;

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
	 * Initializes the model class.
	 *
	 * @param string|null $modelClass The model class reference using ::class.
	 */
	public function __construct(protected ?string $modelClass = UserRole::class)
	{
		parent::__construct($modelClass);
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
			$this->model()->deleteUserRole($data->userId, $data->roleId)
		);
	}
}