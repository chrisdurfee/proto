<?php declare(strict_types=1);
namespace Proto\Automation\Processes\Dispatch;

use Proto\Automation\Processes\Routine;
use Proto\Models\Model;

/**
 * QueueRoutine
 *
 * This is a base class for queue routines.
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
		$queue = $this->fetchData();
		if (!$queue || count($queue) < 1)
		{
			return;
		}

		$result = $this->processQueue($queue);
		if ($result)
		{
			$this->deleteQueue();
		}
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
	 * @param array $queue
	 * @return bool
	 */
	protected function processQueue(array $queue): bool
	{
		$this->updateQueueStatus('sending');

		$success = true;
		foreach ($queue as $item)
		{
			if (!$item)
			{
				continue;
			}

			$result = $this->dispatch($item);
			if ($result === false)
			{
				$success = false;
			}
		}
		return $success;
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