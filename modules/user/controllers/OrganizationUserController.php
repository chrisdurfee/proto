<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Proto\Controllers\ResourceController as Controller;
use Modules\User\Models\OrganizationUser;
use Modules\User\Auth\Policies\OrganizationPolicy;

/**
 * OrganizationUserController
 *
 * @package Modules\User\Controllers
 */
class OrganizationUserController extends Controller
{
	/**
	 * @var string|null $policy
	 */
	protected ?string $policy = OrganizationPolicy::class;

	/**
	 * Initializes the model class.
	 *
	 * @param string|null $modelClass The model class reference using ::class.
	 */
	public function __construct(protected ?string $modelClass = OrganizationUser::class)
	{
		parent::__construct();
	}
}