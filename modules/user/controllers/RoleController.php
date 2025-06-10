<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Modules\User\Auth\Policies\RolePolicy;
use Proto\Controllers\ResourceController as Controller;
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
	 * @var string|null $policy
	 */
	protected ?string $policy = RolePolicy::class;

	/**
	 * Initializes the model class.
	 *
	 * @param string|null $model The model class reference using ::class.
	 */
	public function __construct(protected ?string $model = Role::class)
	{
		parent::__construct();
	}
}