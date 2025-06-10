<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Modules\User\Auth\Policies\OrganizationPolicy;
use Proto\Controllers\ResourceController as Controller;
use Modules\User\Models\Organization;

/**
 * OrganizationController
 *
 * This is the controller class for the model "Organization".
 *
 * @package Modules\User\Controllers
 */
class OrganizationController extends Controller
{
	/**
	 * @var string|null $policy
	 */
	protected ?string $policy = OrganizationPolicy::class;

	/**
	 * Initializes the model class.
	 *
	 * @param string|null $model The model class reference using ::class.
	 */
	public function __construct(protected ?string $model = Organization::class)
	{
		parent::__construct();
	}
}