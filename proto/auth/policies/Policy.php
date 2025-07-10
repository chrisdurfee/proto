<?php declare(strict_types=1);
namespace Proto\Auth\Policies;

use Proto\Controllers\ControllerInterface;
use Proto\Http\Router\Request;

/**
 * Class Policy
 *
 * Base class for authentication policies.
 *
 * @package Proto\Auth\Policies
 * @abstract
 */
abstract class Policy
{
	/**
	 * This will create a new instance of the policy.
	 *
	 * @param ?ControllerInterface $controller The controller instance associated with this policy.
	 * @return void
	 */
	public function __construct(protected ?ControllerInterface $controller = null) {}

	/**
	 * This will get the resource ID from the request.
	 *
	 * @param Request $request
	 * @return int|null
	 */
	protected function getResourceId(Request $request): ?int
	{
		$id = $request->getInt('id') ?? $request->params()->id ?? null;
		return (isset($id) && is_numeric($id)) ? (int) $id : null;
	}
}
