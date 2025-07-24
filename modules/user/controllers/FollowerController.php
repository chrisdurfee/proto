<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Modules\User\Auth\Policies\FollowerPolicy;
use Proto\Controllers\ApiController as Controller;
use Modules\User\Services\User\FollowerService;
use Proto\Http\Router\Request;

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
		$followerId = $request->getInt('userId') ?? null;
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
		$result = $this->followerService->notifyNewFollower($userId, $followerId, (bool)$queue);
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
		$followerId = $request->getInt('userId') ?? null;
		if (!isset($followerId))
		{
			return $this->error('Follower user ID is required.');
		}

		$userId = $this->getResourceId($request);
		if ($userId === null)
		{
			return $this->error('Invalid user ID to follow.');
		}

		$result = $this->followerService->toggleFollower((int)$userId, $followerId);
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
		$followerId = $request->getInt('userId') ?? null;
		if (!isset($followerId))
		{
			return $this->error('Follower user ID is required.');
		}

		$userId = $this->getResourceId($request);
		if ($userId === null)
		{
			return $this->error('Invalid user ID to follow.');
		}

		$result = $this->followerService->followUser((int)$userId, $followerId);
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
		$followerId = $request->getInt('userId') ?? null;
		if (!isset($followerId))
		{
			return $this->error('Follower user ID is required.');
		}

		$userId = $this->getResourceId($request);
		if ($userId === null)
		{
			return $this->error('Invalid user ID to unfollow.');
		}

		$result = $this->followerService->unfollowUser((int)$userId, $followerId);
		if (!$result->success)
		{
			return $this->error($result->message);
		}

		return $this->response($result->result);
	}
}