<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Dispatch;

use Proto\Automation\Processes\Dispatch\QueueRoutine;
use Proto\Models\Model;
use Proto\Tests\Test;

/**
 * QueueRoutineTest
 *
 * One failed (or throwing) item used to leave the entire claimed batch
 * in `sending` forever. Items must now be settled individually: sent
 * rows removed, failed rows released with backoff, abandoned `sending`
 * rows reclaimed.
 *
 * @package Proto\Tests\Unit\Dispatch
 */
final class QueueRoutineTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testFailedItemDoesNotStrandTheRestOfTheBatch(): void
	{
		$store = new FakeQueueStore([
			(object)['id' => 1, 'attempts' => 0, 'result' => true],
			(object)['id' => 2, 'attempts' => 0, 'result' => false],
			(object)['id' => 3, 'attempts' => 0, 'result' => true]
		]);

		$routine = new FakeQueueRoutine($store);
		$routine->run();

		$this->assertSame([1, 3], $store->sent);
		$this->assertCount(1, $store->failed);
		$this->assertSame(2, $store->failed[0]['id']);
		$this->assertSame(5, $store->failed[0]['maxAttempts']);
		$this->assertSame([1, 2, 3], $routine->dispatched);
	}

	/**
	 * @return void
	 */
	public function testThrowingItemIsTreatedAsFailureAndBatchContinues(): void
	{
		$store = new FakeQueueStore([
			(object)['id' => 1, 'attempts' => 0, 'result' => 'throw'],
			(object)['id' => 2, 'attempts' => 0, 'result' => true]
		]);

		$routine = new FakeQueueRoutine($store);
		$routine->run();

		$this->assertSame([2], $store->sent);
		$this->assertSame(1, $store->failed[0]['id']);
	}

	/**
	 * @return void
	 */
	public function testRetryDelayBacksOffExponentiallyAndIsCapped(): void
	{
		$store = new FakeQueueStore([
			(object)['id' => 7, 'attempts' => 2, 'result' => false]
		]);

		$routine = new FakeQueueRoutine($store);
		$routine->run();

		// third attempt failed: 60 * 2^2 = 240s, passed as a delay so the
		// database computes the timestamp on its own clock
		$this->assertSame(240, $store->failed[0]['nextAttemptAt']);

		$this->assertSame(60, $routine->delayFor(1));
		$this->assertSame(120, $routine->delayFor(2));
		$this->assertSame(3600, $routine->delayFor(50));
	}

	/**
	 * A batch that outlives half the stale window hands the rest back
	 * instead of sending rows the next run may already have reclaimed.
	 *
	 * @return void
	 */
	public function testSlowBatchReleasesRemainingItems(): void
	{
		$store = new FakeQueueStore([
			(object)['id' => 1, 'attempts' => 0, 'result' => true],
			(object)['id' => 2, 'attempts' => 0, 'result' => true],
			(object)['id' => 3, 'attempts' => 0, 'result' => true]
		]);

		$routine = new FakeQueueRoutine($store);
		$routine->secondsPerItem = 1000.0;
		$routine->run();

		$this->assertSame([1], $routine->dispatched);
		$released = array_values(array_filter($store->calls, fn(array $call): bool => $call[0] === 'release'));
		$this->assertSame([2, 3], array_map(fn(array $call): mixed => $call[1][0], $released));
		$this->assertSame([], $store->failed, 'Released items must not use an attempt.');
	}

	/**
	 * @return void
	 */
	public function testStaleSendingRowsAreReclaimedBeforeClaiming(): void
	{
		$store = new FakeQueueStore([]);
		$routine = new FakeQueueRoutine($store);
		$routine->run();

		$this->assertSame(['reclaimStale', 'updateAgentId', 'getPending'], array_column($store->calls, 0));
		$this->assertSame([30, 5], $store->calls[0][1]);
	}
}

/**
 * In-memory stand-in for the queue storage.
 */
final class FakeQueueStore
{
	/**
	 * @var array
	 */
	public array $calls = [];

	/**
	 * @var array
	 */
	public array $sent = [];

	/**
	 * @var array
	 */
	public array $failed = [];

	/**
	 * @param array $rows
	 */
	public function __construct(public array $rows)
	{
	}

	/**
	 * Records a storage call and returns a canned result.
	 *
	 * @param string $method
	 * @param array $args
	 * @return mixed
	 */
	public function handle(string $method, array $args): mixed
	{
		$this->calls[] = [$method, $args];
		switch ($method)
		{
			case 'getPending':
				return $this->rows;
			case 'markSent':
				$this->sent[] = $args[0];
				return true;
			case 'markFailed':
				$this->failed[] = [
					'id' => $args[0],
					'maxAttempts' => $args[2],
					'nextAttemptAt' => $args[3]
				];
				return true;
			default:
				return true;
		}
	}
}

/**
 * Model double that forwards storage calls to the fake store.
 */
final class FakeQueueModel extends Model
{
	/**
	 * @param FakeQueueStore $store
	 */
	public function __construct(private FakeQueueStore $store)
	{
	}

	/**
	 * @param string $method
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call(string $method, array $arguments): mixed
	{
		return $this->store->handle($method, $arguments);
	}
}

/**
 * Queue routine that dispatches by the row's `result` field.
 */
final class FakeQueueRoutine extends QueueRoutine
{
	/**
	 * @var bool
	 */
	protected bool $setLimits = false;

	/**
	 * @var array
	 */
	public array $dispatched = [];

	/**
	 * Simulated seconds each dispatch takes (0 = real clock).
	 *
	 * @var float
	 */
	public float $secondsPerItem = 0.0;

	/**
	 * @var float
	 */
	private float $clock = 0.0;

	/**
	 * @return float
	 */
	protected function now(): float
	{
		return $this->secondsPerItem > 0 ? $this->clock : parent::now();
	}

	/**
	 * @param FakeQueueStore $store
	 */
	public function __construct(private FakeQueueStore $store)
	{
		parent::__construct();
	}

	/**
	 * @param object|null $data
	 * @return Model
	 */
	protected function getModel(?object $data = null): Model
	{
		return new FakeQueueModel($this->store);
	}

	/**
	 * @param object $item
	 * @return bool
	 */
	protected function dispatch(object $item): bool
	{
		$this->dispatched[] = $item->id;
		$this->clock += $this->secondsPerItem;
		if ($item->result === 'throw')
		{
			throw new \RuntimeException('provider exploded');
		}
		return $item->result;
	}

	/**
	 * @param int $attempt
	 * @return int
	 */
	public function delayFor(int $attempt): int
	{
		return $this->getRetryDelay($attempt);
	}
}
