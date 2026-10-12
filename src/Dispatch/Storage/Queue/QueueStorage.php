<?php declare(strict_types=1);
namespace Proto\Dispatch\Storage\Queue;

use Proto\Storage\Storage;

/**
 * QueueStorage
 *
 * This will handle the queue storage.
 *
 * Rows move through `pending` -> `sending` -> deleted (sent). A failed
 * send goes back to `pending` with an incremented `attempts` counter
 * and a `next_attempt_at` backoff time, or to `error` once the attempt
 * budget is spent. Rows stuck in `sending` (crashed worker) are
 * reclaimed by {@see reclaimStale()}.
 *
 * @package Proto\Dispatch\Storage\Queue
 */
class QueueStorage extends Storage
{
	/**
	 * This will set the agent id to the next batch of items.
	 *
	 * Only claims rows that are pending, unclaimed, and whose backoff
	 * window (if any) has elapsed.
	 *
	 * @param string $agentId
	 * @param int $count
	 * @return bool
	 */
	public function updateAgentId(string $agentId, int $count = 300): bool
	{
		$sql = $this->table()
			->update('agent_id = ?')
			->where(
				'status = "pending"',
				'agent_id IS NULL',
				'(next_attempt_at IS NULL OR next_attempt_at <= NOW())'
			)
			->orderBy('priority ASC')
			->limit($count);

		return $this->execute($sql, [$agentId]);
	}

	/**
	 * This will get the next batch of pending items.
	 *
	 * @param string $agentId
	 * @param int $count
	 * @return array
	 */
	public function getPending(string $agentId, int $count = 300): array
	{
		$sql = $this->select()
			->where('status = "pending"', 'agent_id = ?')
			->orderBy('priority ASC')
			->limit($count);

		return $this->fetch($sql, [$agentId]);
	}

	/**
	 * This will update the batch item status.
	 *
	 * Also stamps `updated_at` so a crashed worker's rows can be
	 * recognised as stale by {@see reclaimStale()}.
	 *
	 * @param string $agentId
	 * @param string $status
	 * @return bool
	 */
	public function updateStatusByAgentId(string $agentId, string $status): bool
	{
		$sql = $this->table()
			->update('status = ?', 'updated_at = NOW()')
			->where('status = "pending"', 'agent_id = ?');

		return $this->execute($sql, [$status, $agentId]);
	}

	/**
	 * This will remove a sent item from the queue.
	 *
	 * @param int|string $id
	 * @param string $agentId
	 * @return bool
	 */
	public function markSent(int|string $id, string $agentId): bool
	{
		$sql = $this->table()
			->delete()
			->where('id = ?', 'agent_id = ?');

		return $this->execute($sql, [$id, $agentId]);
	}

	/**
	 * This will release a failed item for a later retry, or mark it
	 * as `error` once it has used up its attempts.
	 *
	 * @param int|string $id
	 * @param string $agentId
	 * @param int $maxAttempts
	 * @param string $nextAttemptAt Y-m-d H:i:s
	 * @return bool
	 */
	public function markFailed(int|string $id, string $agentId, int $maxAttempts, string $nextAttemptAt): bool
	{
		// MySQL evaluates SET assignments left to right, so `status`
		// must be computed before `attempts` is incremented.
		$sql = $this->table()
			->update()
			->setRaw('status = IF(attempts + 1 >= ?, "error", "pending")')
			->setRaw('attempts = attempts + 1')
			->setRaw('agent_id = NULL')
			->setRaw('next_attempt_at = ?')
			->setRaw('updated_at = NOW()')
			->where('id = ?', 'agent_id = ?');

		return $this->execute($sql, [$maxAttempts, $nextAttemptAt, $id, $agentId]);
	}

	/**
	 * This will reclaim rows left in `sending` by a crashed worker.
	 *
	 * A reclaim counts as an attempt so a message that crashes the
	 * worker every time cannot loop forever.
	 *
	 * @param int $minutes Rows untouched for this long are stale.
	 * @param int $maxAttempts
	 * @return bool
	 */
	public function reclaimStale(int $minutes, int $maxAttempts): bool
	{
		$sql = $this->table()
			->update()
			->setRaw('status = IF(attempts + 1 >= ?, "error", "pending")')
			->setRaw('attempts = attempts + 1')
			->setRaw('agent_id = NULL')
			->setRaw('updated_at = NOW()')
			->where('status = "sending"', 'COALESCE(updated_at, created_at) < (NOW() - INTERVAL ? MINUTE)');

		return $this->execute($sql, [$maxAttempts, $minutes]);
	}

	/**
	 * This will delete the batch.
	 *
	 * @param string $agentId
	 * @return bool
	 */
	public function deleteByAgentId(string $agentId): bool
	{
		$sql = $this->table()
			->delete()
			->where('status = "sending"', 'agent_id = ?');

		return $this->execute($sql, [$agentId]);
	}
}
