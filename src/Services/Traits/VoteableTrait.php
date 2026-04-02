<?php declare(strict_types=1);
namespace Proto\Services\Traits;

/**
 * VoteableTrait
 *
 * Shared vote/score pattern for services.
 *
 * Handles up/down voting with toggle-off, vote-switching, and atomic
 * score updates. Fixes the common bug of calling update() on a
 * stdClass returned by getBy().
 *
 * @package Proto\Services\Traits
 */
trait VoteableTrait
{
	/**
	 * Cast or toggle a vote on an item.
	 *
	 * Behavior:
	 * - No existing vote → create vote, increment/decrement score
	 * - Same vote exists → remove vote (toggle off), reverse score change
	 * - Opposite vote exists → update vote, swing score by 2×
	 *
	 * @param string $voteModelClass The vote model class.
	 * @param string $parentModelClass The parent/target model class.
	 * @param int $userId The voting user's ID.
	 * @param int $itemId The target item's ID.
	 * @param string $itemIdField The FK field on the vote model (e.g., 'postId').
	 * @param string $direction 'up' or 'down'.
	 * @param string $scoreField The counter field on the parent model (default: 'score').
	 * @return object {direction: ?string, score: int}
	 */
	protected function vote(
		string $voteModelClass,
		string $parentModelClass,
		int $userId,
		int $itemId,
		string $itemIdField,
		string $direction,
		string $scoreField = 'score'
	): object
	{
		$existing = $voteModelClass::getBy([
			'userId' => $userId,
			$itemIdField => $itemId
		]);

		$intValue = ($direction === 'up') ? 1 : -1;

		if ($existing)
		{
			if ((int)$existing->value === $intValue)
			{
				// Same vote — toggle off
				$voteModelClass::remove($existing->id);

				if ($intValue > 0)
				{
					$parentModelClass::atomicDecrement($itemId, $scoreField, abs($intValue));
				}
				else
				{
					$parentModelClass::atomicIncrement($itemId, $scoreField, abs($intValue));
				}

				return (object)[
					'direction' => null,
					'score' => $this->getVoteScore($parentModelClass, $itemId, $scoreField)
				];
			}

			// Opposite vote — update via builder to avoid stdClass issue
			$voteModelClass::builder()
				->update()
				->set(['value' => $intValue])
				->where('id = ?')
				->execute([(int)$existing->id]);

			// Swing is 2× (remove old direction, add new)
			if ($intValue > 0)
			{
				$parentModelClass::atomicIncrement($itemId, $scoreField, 2);
			}
			else
			{
				$parentModelClass::atomicDecrement($itemId, $scoreField, 2, false);
			}
		}
		else
		{
			$vote = new $voteModelClass((object)[
				'userId' => $userId,
				$itemIdField => $itemId,
				'value' => $intValue
			]);
			$vote->add();

			if ($intValue > 0)
			{
				$parentModelClass::atomicIncrement($itemId, $scoreField);
			}
			else
			{
				$parentModelClass::atomicDecrement($itemId, $scoreField, 1, false);
			}
		}

		return (object)[
			'direction' => $direction,
			'score' => $this->getVoteScore($parentModelClass, $itemId, $scoreField)
		];
	}

	/**
	 * Get the current score for an item.
	 *
	 * @param string $modelClass The model class.
	 * @param int $id The item ID.
	 * @param string $field The score field name.
	 * @return int
	 */
	private function getVoteScore(string $modelClass, int $id, string $field): int
	{
		$item = $modelClass::get($id);
		return (int)($item->$field ?? 0);
	}
}
