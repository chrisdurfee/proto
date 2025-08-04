<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Modules\User\Auth\Policies\FollowerPolicy;
use Proto\Controllers\ApiController as Controller;
use Modules\User\Services\User\FollowerService;
use Proto\Http\Router\Request;
use Modules\User\Models\User;

/**
 * FollowerController
 *
 * @package Modules\User\Controllers
 */
class FollowerController extends Controller
{
	/**
	 * @var string|null $policy
	 */
	protected ?string $policy = FollowerPolicy::class;

	/**
	 * FollowerController constructor.
	 *
	 * @param FollowerService $followerService
	 * @return void
	 */
	public function __construct(
		protected FollowerService $followerService = new FollowerService()
	)
	{
	}

	/**
	 * Notify a user about a new follower.
	 *
	 * @param Request $request The request object.
	 * @return object
	 */
	public function notify(Request $request): object
	{
		$followerId = $request->params()->followerId ?? null;
		if (!isset($followerId))
		{
			return $this->error('Follower user ID is required.');
		}

		$userId = $this->getResourceId($request);
		if ($userId === null)
		{
			return $this->error('Invalid user ID to follow.');
		}

		$queue = $request->input('queue');
		$result = $this->followerService->notifyNewFollower((int)$userId, (int)$followerId, (bool)$queue);
		if (!$result->success)
		{
			return $this->error($result->message);
		}

		return $this->response($result->result);
	}

	/**
	 * Toggles the follower status.
	 *
	 * @param Request $request The request object.
	 * @return object
	 */
	public function toggle(Request $request): object
	{
		$followerId = $request->params()->followerId ?? null;
		if (!isset($followerId))
		{
			return $this->error('Follower user ID is required.');
		}

		$userId = $this->getResourceId($request);
		if ($userId === null)
		{
			return $this->error('Invalid user ID to follow.');
		}

		$result = $this->followerService->toggleFollower((int)$userId, (int)$followerId);
		if (!$result->success)
		{
			return $this->error($result->message);
		}

		return $this->response($result->result);
	}

	/**
	 * Adds a follower.
	 *
	 * @param Request $request The request object.
	 * @return object
	 */
	public function follow(Request $request): object
	{
		$followerId = $request->params()->followerId ?? null;
		if (!isset($followerId))
		{
			return $this->error('Follower user ID is required.');
		}

		$userId = $this->getResourceId($request);
		if ($userId === null)
		{
			return $this->error('Invalid user ID to follow.');
		}

		$result = $this->followerService->followUser((int)$userId, (int)$followerId);
		if (!$result->success)
		{
			return $this->error($result->message);
		}

		return $this->response($result->result);
	}

	/**
	 * Removes a follower.
	 *
	 * @param Request $request The request object.
	 * @return object
	 */
	public function unfollow(Request $request): object
	{
		$followerId = $request->params()->followerId ?? null;
		if (!isset($followerId))
		{
			return $this->error('Follower user ID is required.');
		}

		$userId = $this->getResourceId($request);
		if ($userId === null)
		{
			return $this->error('Invalid user ID to unfollow.');
		}

		$result = $this->followerService->unfollowUser((int)$userId, (int)$followerId);
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
		$result = $user->followers()->all($filter, $offset, $limit, [
			'search' => $search,
			'custom' => $custom,
			'dates' => $dates,
			'orderBy' => $orderBy,
			'groupBy' => $groupBy
		]);
		return $this->response($result);
	}
}