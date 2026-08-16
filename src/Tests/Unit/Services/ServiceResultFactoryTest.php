<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Services;

use Proto\Services\ServiceResult;
use Proto\Services\Traits\ServiceResultFactory;
use Proto\Tests\Test;

/**
 * ServiceResultFactoryTest
 *
 * Verifies that `ok()` / `fail()` work correctly on a plain class that
 * only uses {@see ServiceResultFactory}, independent of
 * {@see \Proto\Services\Service} — the adoption path for classes that
 * cannot extend `Service` (e.g. because they already define an
 * incompatible `success()`).
 *
 * @package Proto\Tests\Unit\Services
 */
final class ServiceResultFactoryTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testOkAndFailProduceCorrectServiceResultsStandalone(): void
	{
		$instance = new class
		{
			use ServiceResultFactory;

			public function createThing(bool $shouldSucceed): ServiceResult
			{
				return $shouldSucceed ? $this->ok(['id' => 1]) : $this->fail('Nope', 'THING_FAILED');
			}
		};

		$ok = $instance->createThing(true);
		$this->assertInstanceOf(ServiceResult::class, $ok);
		$this->assertTrue($ok->success);
		$this->assertEquals(['id' => 1], $ok->data);

		$fail = $instance->createThing(false);
		$this->assertTrue($fail->failed());
		$this->assertEquals('Nope', $fail->error);
		$this->assertEquals('THING_FAILED', $fail->code);
	}

	/**
	 * The trait works even on a class whose own base class already has
	 * an incompatible `success()` method (the exact Rally scenario the
	 * trait exists for): `ok()`/`fail()` do not collide with it.
	 *
	 * @return void
	 */
	public function testTraitDoesNotCollideWithAnIncompatibleBaseClassSuccessMethod(): void
	{
		$instance = new class extends LegacyHttpEnvelopeServiceBase
		{
			use ServiceResultFactory;

			public function doWork(): ServiceResult
			{
				return $this->ok('done');
			}
		};

		// The base class's own success() still works, untouched.
		$this->assertEquals(['ok' => true, 'body' => 'x'], $instance->success('x'));

		// The trait's ok() still returns a real ServiceResult.
		$result = $instance->doWork();
		$this->assertInstanceOf(ServiceResult::class, $result);
		$this->assertTrue($result->success);
		$this->assertEquals('done', $result->data);
	}
}

/**
 * Stand-in for Rally's hand-copied `Common\Services\Service`: defines
 * its own `success()` with an incompatible signature/meaning (an HTTP
 * envelope), which is exactly why it cannot extend
 * `Proto\Services\Service`.
 *
 * @package Proto\Tests\Unit\Services
 */
class LegacyHttpEnvelopeServiceBase
{
	/**
	 * @param mixed $body
	 * @return array
	 */
	public function success(mixed $body): array
	{
		return ['ok' => true, 'body' => $body];
	}
}
