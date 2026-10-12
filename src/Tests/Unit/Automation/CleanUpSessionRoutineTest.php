<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Automation;

use Proto\Automation\Processes\Session\CleanUpSessionRoutine;
use Proto\Database\QueryBuilder\Delete;
use Proto\Tests\Test;

/**
 * CleanUpSessionRoutineTest
 *
 * The routine used to call instance finders statically (a fatal). It
 * now deletes in bounded batches until a batch comes back short.
 *
 * @package Proto\Tests\Unit\Automation
 */
final class CleanUpSessionRoutineTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testDrainStopsAfterShortBatch(): void
	{
		$routine = new DrainProbeRoutine();
		$batches = [1000, 1000, 12];
		$calls = 0;

		$total = $routine->exposeDrain(function () use (&$batches, &$calls): int
		{
			$calls++;
			return array_shift($batches) ?? 0;
		});

		$this->assertSame(2012, $total);
		$this->assertSame(3, $calls);
	}

	/**
	 * @return void
	 */
	public function testBatchDeleteRendersBoundedStatement(): void
	{
		$sql = (string)(new Delete('user_sessions'))->where('updated_at < ?')->limit(1000);
		$this->assertStringContainsString('DELETE FROM user_sessions', $sql);
		$this->assertMatchesRegularExpression('/WHERE\s+updated_at < \?/', $sql);
		$this->assertStringContainsString('LIMIT 1000', $sql);
	}
}

/**
 * @package Proto\Tests\Unit\Automation
 */
final class DrainProbeRoutine extends CleanUpSessionRoutine
{
	/**
	 * @param callable(): int $deleteBatch
	 * @return int
	 */
	public function exposeDrain(callable $deleteBatch): int
	{
		return $this->drain($deleteBatch);
	}
}
