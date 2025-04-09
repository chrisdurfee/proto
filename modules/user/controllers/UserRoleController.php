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
}