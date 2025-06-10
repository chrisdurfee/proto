<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Modules\User\Auth\Policies\PermissionPolicy;
use Proto\Controllers\ResourceController as Controller;
use Modules\User\Models\Permission;

/**
 * PermissionController
 *
 * @package Modules\User\Controllers
 */
class PermissionController extends Controller
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
	public function __construct(protected ?string $model = Permission::class)
	{
		parent::__construct();
	}
}