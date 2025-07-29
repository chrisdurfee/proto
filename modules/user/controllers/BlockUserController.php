<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Modules\User\Auth\Policies\FollowerPolicy;
use Proto\Controllers\ApiController as Controller;
use Modules\User\Services\User\BlockUserService;
use Proto\Http\Router\Request;
use Modules\User\Models\User;

/**
 * BlockUserController
 *
 * @package Modules\User\Controllers
 */
class BlockUserController extends Controller
{
	/**
	 * @var string|null $policy
	 */
	protected ?string $policy = FollowerPolicy::class;

	/**
	 * BlockUserController constructor.
	 *
	 * @param BlockUserService $blockUserService
	 * @return void
	 */
	public function __construct(
		protected BlockUserService $blockUserService = new BlockUserService()
	)
	{
	}

	/**
	 * Toggles the block status.
	 *
	 * @param Request $request The request object.
	 * @return object
	 */
	public function toggle(Request $request): object
	{
		$blockUserId = $request->params()->blockUserId ?? null;
		if (!isset($blockUserId))
		{
			return $this->error('Blocked user ID is required.');
		}

		$userId = $this->getResourceId($request);
		if ($userId === null)
		{
			return $this->error('Invalid user ID to block.');
		}

		$result = $this->blockUserService->toggleBlock((int)$userId, (int)$blockUserId);
		if (!$result->success)
		{
			return $this->error($result->message);
		}

		return $this->response($result->result);
	}

	/**
	 * Adds a blocked user.
	 *
	 * @param Request $request The request object.
	 * @return object
	 */
	public function block(Request $request): object
	{
		$blockUserId = $request->params()->blockUserId ?? null;
		if (!isset($blockUserId))
		{
			return $this->error('Blocked user ID is required.');
		}

		$userId = $this->getResourceId($request);
		if ($userId === null)
		{
			return $this->error('Invalid user ID.');
		}

		$result = $this->blockUserService->blockUser((int)$userId, (int)$blockUserId);
		if (!$result->success)
		{
			return $this->error($result->message);
		}

		return $this->response($result->result);
	}

	/**
	 * Removes a blocked user.
	 *
	 * @param Request $request The request object.
	 * @return object
	 */
	public function unblock(Request $request): object
	{
		$blockUserId = $request->params()->blockUserId ?? null;
		if (!isset($blockUserId))
		{
			return $this->error('Blocked user ID is required.');
		}

		$userId = $this->getResourceId($request);
		if ($userId === null)
		{
			return $this->error('Invalid user ID.');
		}

		$result = $this->blockUserService->unblockUser((int)$userId, (int)$blockUserId);
		if (!$result->success)
		{
			return $this->error($result->message);
		}

		return $this->response($result->result);
	}

	/**
	 * Retrieve all records.
	 *
	 * @param array|object|null $filter Filter criteria.
	 * @param int|null $offset Offset.
	 * @param int|null $limit Count.
	 * @param array|null $modifiers Modifiers.
	 * @return object
	 */
	public function all(Request $request): object
	{
		$userId = $this->getResourceId($request);
		if ($userId === null)
		{
			return $this->error('Invalid user ID.');
		}

		$filter = $this->getFilter($request);
		$offset = $request->getInt('offset') ?? 0;
		$limit = $request->getInt('limit') ?? 50;
		$search = $request->input('search');
		$custom = $request->input('custom');
		$dates = $this->setDateModifier($request);
		$orderBy = $this->setOrderByModifier($request);
		$groupBy = $this->setGroupByModifier($request);

		$user = User::get($userId);
		$result = $user->blocked()->all($filter, $offset, $limit, [
			'search' => $search,
			'custom' => $custom,
			'dates' => $dates,
			'orderBy' => $orderBy,
			'groupBy' => $groupBy
		]);
		return $this->response($result);
	}
}