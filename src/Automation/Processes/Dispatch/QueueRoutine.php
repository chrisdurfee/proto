<?php declare(strict_types=1);
namespace Proto\Automation\Processes\Dispatch;

use Proto\Automation\Processes\Routine;
use Proto\Models\Model;

/**
 * QueueRoutine
 *
 * This is a base class for queue routines.
 *
 * Each claimed item is settled individually: a sent item is removed
 * from the queue, a failed item (false result or thrown exception) is
 * released back to `pending` with an incremented attempt counter and a
 * backoff delay, and moves to `error` once `$maxAttempts` is reached.
 * Rows left in `sending` by a crashed worker are reclaimed after
 * `$staleMinutes`.
 *
 * Requires the `attempts` and `next_attempt_at` queue columns
 * (migration AddRetryColumnsToDispatchQueues).
 *
 * @package Proto\Automation\Processes\Dispatch
 */
abstract class QueueRoutine extends Routine
{
	/**
	 * @var string $agentId
	 */
	protected string $agentId;

	/**
	 * @var int $batchSize
	 */
	protected int $batchSize = 300;

	/**
	 * Attempts (including the first) before an item is marked `error`.
	 *
	 * @var int $maxAttempts
	 */
	protected int $maxAttempts = 5;

	/**
	 * Base retry delay in seconds; doubles with each attempt.
	 *
	 * @var int $retryDelaySeconds
	 */
	protected int $retryDelaySeconds = 60;

	/**
	 * Upper bound for the retry delay in seconds.
	 *
	 * @var int $maxRetryDelaySeconds
	 */
	protected int $maxRetryDelaySeconds = 3600;

	/**
	 * Minutes after which a `sending` row is considered abandoned.
	 *
	 * @var int $staleMinutes
	 */
	protected int $staleMinutes = 30;

	/**
	 * This will construct the queue routine.
	 *
	 * @param string|null $date
	 * @return void
	 */
	public function __construct(?string $date = null)
	{
		parent::__construct($date);
		$this->agentId = $this->createtAgentId();
	}

	/**
	 * This will create an id.
	 *
	 * @return string
	 */
	protected function createtAgentId(): string
	{
		$timestamp = round(microtime(true) * 1000);
		$uniqueId = bin2hex(random_bytes(8));
		return (string)(dechex((int)$timestamp) . $uniqueId);
	}

	/**
	 * This will get the queue model.
	 *
	 * @param object|null $data
	 * @return Model
	 */
	abstract protected function getModel(?object $data = null): Model;

	/**
	 * Starts the routine's process.
	 *
	 * @return void
	 */
	protected function process(): void
	{
		$this->reclaimStale();

		$queue = $this->fetchData();
		if (!$queue || count($queue) < 1)
		{
			return;
		}

		$this->processQueue($queue);
	}

	/**
	 * This will release rows abandoned in `sending` by a crashed worker.
	 *
	 * @return bool
	 */
	protected function reclaimStale(): bool
	{
		$model = $this->getModel();
		return (bool)$model->reclaimStale($this->staleMinutes, $this->maxAttempts);
	}

	/**
	 * This will get the enqueued items.
	 *
	 * @return array
	 */
	protected function fetchData(): array
	{
		$model = $this->getModel();

		// claim batch
		$model->updateAgentId($this->agentId, $this->batchSize);

		// get queue
		return $model->getPending($this->agentId, $this->batchSize);
	}

	/**
	 * This will update the queue item statues.
	 *
	 * @param string $status
	 * @return bool
	 */
	protected function updateQueueStatus(string $status): bool
	{
		$model = $this->getModel();
		return $model->updateStatusByAgentId($this->agentId, $status);
	}

	/**
	 * This will process the queue.
	 *
	 * One failing (or throwing) item never prevents the others from
	 * being sent or settled.
	 *
	 * @param array $queue
	 * @return bool True when every item was sent.
	 */
	protected function processQueue(array $queue): bool
	{
		$this->updateQueueStatus('sending');

		/**
		 * Stop sending once half the stale window has passed: rows still
		 * in `sending` after `$staleMinutes` are reclaimed by the next run,
		 * and a slow batch (300 items x a 15s SMTP timeout) would otherwise
		 * be sent a second time. Unsent rows are handed back untouched.
		 */
		$deadline = $this->now() + ($this->staleMinutes * 60) / 2;

		$success = true;
		foreach ($queue as $item)
		{
			if (!$item)
			{
				continue;
			}

			if ($this->now() > $deadline)
			{
				$this->releaseItem($item);
				continue;
			}

			$sent = $this->dispatchSafely($item);
			if ($sent)
			{
				$this->markSent($item);
				continue;
			}

			$success = false;
			$this->markFailed($item);
		}
		return $success;
	}

	/**
	 * Dispatches one item, treating any throwable as a failed send.
	 *
	 * @param object $item
	 * @return bool
	 */
	protected function dispatchSafely(object $item): bool
	{
		try
		{
			return $this->dispatch($item) === true;
		}
		catch (\Throwable $e)
		{
			error(
				'Dispatch queue item ' . ($item->id ?? '?') . ' failed: ' . $e->getMessage(),
				__FILE__,
				__LINE__
			);
			return false;
		}
	}

	/**
	 * Current time in seconds (overridable in tests).
	 *
	 * @return float
	 */
	protected function now(): float
	{
		return microtime(true);
	}

	/**
	 * Hands an unsent item back to the queue without using an attempt.
	 *
	 * @param object $item
	 * @return bool
	 */
	protected function releaseItem(object $item): bool
	{
		$model = $this->getModel();
		return (bool)$model->release($item->id, $this->agentId);
	}

	/**
	 * This will remove a sent item from the queue.
	 *
	 * @param object $item
	 * @return bool
	 */
	protected function markSent(object $item): bool
	{
		$model = $this->getModel();
		return (bool)$model->markSent($item->id, $this->agentId);
	}

	/**
	 * This will release a failed item for retry or mark it `error`.
	 *
	 * @param object $item
	 * @return bool
	 */
	protected function markFailed(object $item): bool
	{
		$attempt = ((int)($item->attempts ?? 0)) + 1;

		// Pass the delay, not a PHP timestamp: claims compare
		// next_attempt_at with the database's NOW(), and PHP and MySQL
		// time zones are not guaranteed to match.
		$model = $this->getModel();
		return (bool)$model->markFailed($item->id, $this->agentId, $this->maxAttempts, $this->getRetryDelay($attempt));
	}

	/**
	 * Gets the exponential backoff delay for an attempt number.
	 *
	 * @param int $attempt The attempt that just failed (1-based).
	 * @return int Seconds.
	 */
	protected function getRetryDelay(int $attempt): int
	{
		$exponent = min(max(0, $attempt - 1), 20);
		$delay = $this->retryDelaySeconds * (2 ** $exponent);
		return (int)min($delay, $this->maxRetryDelaySeconds);
	}

	/**
	 * This will dispatch the item.
	 *
	 * @param object $item
	 * @return bool
	 */
	abstract protected function dispatch(object $item): bool;

	/**
	 * This will delete the items from the queue.
	 *
	 * @return bool
	 */
	protected function deleteQueue(): bool
	{
		$model = $this->getModel();
		return $model->deleteByAgentId($this->agentId);
	}
}
