<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Proto\Controllers\ModelController as Controller;
use Modules\User\Models\Role;

/**
 * RoleController
 *
 * This is the controller class for the "roles" table.
 *
 * @package Modules\User\Controllers
 */
class RoleController extends Controller
{
	/**
	 * Initializes the model class.
	 *
	 * @param string|null $modelClass The model class reference using ::class.
	 */
	public function __construct(protected ?string $modelClass = Role::class)
	{
		parent::__construct($modelClass);
	}
}