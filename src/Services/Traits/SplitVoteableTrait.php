<?php declare(strict_types=1);
namespace Proto\Services\Traits;

/**
 * SplitVoteableTrait
 *
 * Up/down voting helper for services whose parent rows carry **two
 * separate counters** (`upvotes` + `downvotes`) instead of a single net
 * score. {@see VoteableTrait} covers the single-score model.
 *
 * Behavior:
 *  - No existing vote   → create vote, increment matching counter
 *  - Same vote exists   → remove vote, decrement matching counter
 *  - Opposite vote exists → flip vote, increment one / decrement the other
 *
 * Storage normalization:
 *  - When `$valueAsInt = true`, stores 1 / -1.
 *  - When `$valueAsInt = false`, stores 'upvote' / 'downvote'.
 *
 * @package Proto\Services\Traits
 */
trait SplitVoteableTrait
{
	/**
	 * Cast or toggle a vote, updating the parent's split counters.
	 *
	 * @param string $voteModelClass Vote model class.
	 * @param string $parentModelClass Parent model class carrying the counters.
	 * @param int $userId Voting user.
	 * @param int $itemId Target item id.
	 * @param string $itemIdField FK field on the vote model.
	 * @param string $direction Logical direction: `'up'` or `'down'`.
	 * @param string $upCounterField Parent column counting upvotes.
	 * @param string $downCounterField Parent column counting downvotes.
	 * @param string $valueField Column on the vote model that stores the vote value.
	 * @param bool $valueAsInt When true, stores 1 / -1; when false, stores 'upvote' / 'downvote'.
	 * @return object {action: 'added'|'removed'|'switched', direction: ?string, up: int, down: int}
	 */
	protected function splitVote(
		string $voteModelClass,
		string $parentModelClass,
		int $userId,
		int $itemId,
		string $itemIdField,
		string $direction,
		string $upCounterField = 'upvotes',
		string $downCounterField = 'downvotes',
		string $valueField = 'value',
		bool $valueAsInt = true
	): object
	{
		$direction = ($direction === 'up' || $direction === 'upvote') ? 'up' : 'down';
		$normalizedValue = $valueAsInt
			? ($direction === 'up' ? 1 : -1)
			: ($direction === 'up' ? 'upvote' : 'downvote');

		$existing = $voteModelClass::getBy([
			'userId' => $userId,
			$itemIdField => $itemId
		]);

		$existingDirection = $existing
			? $this->normalizeStoredSplitVote($existing->{$valueField} ?? null, $valueAsInt)
			: null;

		if ($existing && $existingDirection === $direction)
		{
			$voteModelClass::remove((int)$existing->id);
			$parentModelClass::atomicDecrement(
				$itemId,
				$direction === 'up' ? $upCounterField : $downCounterField
			);

			$counts = $this->readSplitCounters($parentModelClass, $itemId, $upCounterField, $downCounterField);
			return (object)[
				'action' => 'removed',
				'direction' => null,
				'up' => $counts['up'],
				'down' => $counts['down']
			];
		}

		if ($existing)
		{
			$voteModelClass::builder()
				->update()
				->set([$this->splitVoteDbColumn($valueField) => $normalizedValue])
				->where('id = ?')
				->execute([(int)$existing->id]);

			if ($direction === 'up')
			{
				$parentModelClass::atomicIncrement($itemId, $upCounterField);
				$parentModelClass::atomicDecrement($itemId, $downCounterField);
			}
			else
			{
				$parentModelClass::atomicIncrement($itemId, $downCounterField);
				$parentModelClass::atomicDecrement($itemId, $upCounterField);
			}

			$counts = $this->readSplitCounters($parentModelClass, $itemId, $upCounterField, $downCounterField);
			return (object)[
				'action' => 'switched',
				'direction' => $direction,
				'up' => $counts['up'],
				'down' => $counts['down']
			];
		}

		$voteRecord = new $voteModelClass((object)[
			'userId' => $userId,
			$itemIdField => $itemId,
			$valueField => $normalizedValue
		]);
		$voteRecord->add();

		$parentModelClass::atomicIncrement(
			$itemId,
			$direction === 'up' ? $upCounterField : $downCounterField
		);

		$counts = $this->readSplitCounters($parentModelClass, $itemId, $upCounterField, $downCounterField);
		return (object)[
			'action' => 'added',
			'direction' => $direction,
			'up' => $counts['up'],
			'down' => $counts['down']
		];
	}

	/**
	 * @param mixed $stored
	 * @param bool $valueAsInt
	 * @return string|null
	 */
	private function normalizeStoredSplitVote(mixed $stored, bool $valueAsInt): ?string
	{
		if ($stored === null)
		{
			return null;
		}

		if ($valueAsInt)
		{
			return ((int)$stored) >= 1 ? 'up' : 'down';
		}

		$stored = (string)$stored;
		return ($stored === 'up' || $stored === 'upvote') ? 'up' : 'down';
	}

	/**
	 * @param string $field
	 * @return string
	 */
	private function splitVoteDbColumn(string $field): string
	{
		return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $field) ?? $field);
	}

	/**
	 * @param string $parentModelClass
	 * @param int $itemId
	 * @param string $upField
	 * @param string $downField
	 * @return array{up: int, down: int}
	 */
	private function readSplitCounters(string $parentModelClass, int $itemId, string $upField, string $downField): array
	{
		$row = $parentModelClass::get($itemId);
		return [
			'up' => (int)($row->{$upField} ?? 0),
			'down' => (int)($row->{$downField} ?? 0)
		];
	}
}
