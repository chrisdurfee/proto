<?php declare(strict_types=1);
namespace Proto\Services\Traits;

/**
 * ToggleLikeTrait
 *
 * Standardizes the like/toggle pattern across services.
 *
 * Handles the entire flow: check existing → remove + decrement / add + increment → return status.
 * Uses atomic counter operations to prevent race conditions.
 *
 * @package Proto\Services\Traits
 */
trait ToggleLikeTrait
{
	/**
	 * Toggle a like record for a user on a target item.
	 *
	 * @param string $likeModelClass The Like model class (e.g., PostLike::class).
	 * @param string $parentModelClass The parent model class (e.g., Post::class).
	 * @param int $userId The user performing the like.
	 * @param int $itemId The target item ID.
	 * @param string $itemIdField The FK field name on the like model (e.g., 'postId').
	 * @param string $counterField The counter field on the parent model (e.g., 'likeCount').
	 * @return object {liked: bool, likeCount: int}
	 */
	protected function toggleLike(
		string $likeModelClass,
		string $parentModelClass,
		int $userId,
		int $itemId,
		string $itemIdField = 'postId',
		string $counterField = 'likeCount'
	): object
	{
		$existing = $likeModelClass::getBy([
			'userId' => $userId,
			$itemIdField => $itemId
		]);

		if ($existing)
		{
			$likeModelClass::remove($existing->id);
			$parentModelClass::atomicDecrement($itemId, $counterField);
			$liked = false;
		}
		else
		{
			$like = new $likeModelClass((object)[
				'userId' => $userId,
				$itemIdField => $itemId
			]);
			$like->add();
			$parentModelClass::atomicIncrement($itemId, $counterField);
			$liked = true;
		}

		$parent = $parentModelClass::get($itemId);
		return (object)[
			'liked' => $liked,
			$counterField => (int)($parent->$counterField ?? 0)
		];
	}
}
