<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Models;

use Proto\Models\Model;
use Proto\Tests\Test;

/**
 * ModelSkipJoinsExceptionSafetyTest
 *
 * Verifies that `Model::$skipJoins` is always reset even when
 * constructing the model instance throws mid-call (e.g. a
 * misconfigured join callback). Before the fix, `getWithoutJoins()`,
 * `fetchWhereWithoutJoins()`, and `count()` each did
 * `static::$skipJoins = true; ...; static::$skipJoins = false;` with
 * no try/finally, so an exception between the two assignments left
 * `$skipJoins` stuck `true` for the rest of the PHP-FPM worker's
 * lifetime -- silently disabling eager joins for every subsequent
 * request (since the property is declared once on the base `Model`
 * class and is not redeclared per subclass, this affects every model,
 * not just the one that threw).
 *
 * @package Proto\Tests\Unit\Models
 */
final class ModelSkipJoinsExceptionSafetyTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->setSkipJoins(false);
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		$this->setSkipJoins(false);
		parent::tearDown();
	}

	/**
	 * @param bool $value
	 * @return void
	 */
	private function setSkipJoins(bool $value): void
	{
		$property = new \ReflectionProperty(Model::class, 'skipJoins');
		$property->setAccessible(true);
		$property->setValue(null, $value);
	}

	/**
	 * @return bool
	 */
	private function getSkipJoins(): bool
	{
		$property = new \ReflectionProperty(Model::class, 'skipJoins');
		$property->setAccessible(true);
		return $property->getValue();
	}

	/**
	 * @return void
	 */
	public function testGetWithoutJoinsResetsSkipJoinsAfterConstructorException(): void
	{
		try
		{
			ThrowingJoinModel::getWithoutJoins(1);
			$this->fail('Expected RuntimeException was not thrown.');
		}
		catch (\RuntimeException $e)
		{
			$this->assertSame('Simulated misconfigured join callback', $e->getMessage());
		}

		$this->assertFalse(
			$this->getSkipJoins(),
			'$skipJoins must reset to false via finally even when the model constructor throws.'
		);
	}

	/**
	 * @return void
	 */
	public function testFetchWhereWithoutJoinsResetsSkipJoinsAfterConstructorException(): void
	{
		try
		{
			ThrowingJoinModel::fetchWhereWithoutJoins(['id' => 1]);
			$this->fail('Expected RuntimeException was not thrown.');
		}
		catch (\RuntimeException $e)
		{
			$this->assertSame('Simulated misconfigured join callback', $e->getMessage());
		}

		$this->assertFalse($this->getSkipJoins());
	}

	/**
	 * @return void
	 */
	public function testCountResetsSkipJoinsAfterConstructorException(): void
	{
		try
		{
			ThrowingJoinModel::count(['id' => 1]);
			$this->fail('Expected RuntimeException was not thrown.');
		}
		catch (\RuntimeException $e)
		{
			$this->assertSame('Simulated misconfigured join callback', $e->getMessage());
		}

		$this->assertFalse($this->getSkipJoins());
	}

	/**
	 * A subsequent, well-behaved model must not be affected by an
	 * earlier constructor exception (proves the reset is not merely
	 * incidental to this one class).
	 *
	 * @return void
	 */
	public function testSkipJoinsResetDoesNotLeakToOtherModels(): void
	{
		try
		{
			ThrowingJoinModel::getWithoutJoins(1);
		}
		catch (\RuntimeException)
		{
			// expected
		}

		$this->assertFalse($this->getSkipJoins());

		// A normal model can still construct after the throwing model's
		// failure; $skipJoins is not stuck true.
		$model = new HealthyJoinModel();
		$this->assertInstanceOf(HealthyJoinModel::class, $model);
		$this->assertFalse($this->getSkipJoins());
	}
}

/**
 * A model whose join setup always throws, simulating a misconfigured
 * join callback that fails mid-construction.
 *
 * @package Proto\Tests\Unit\Models
 */
final class ThrowingJoinModel extends Model
{
	/**
	 * @var string|null
	 */
	protected static ?string $tableName = 'throwing_join_model';

	/**
	 * @var array
	 */
	protected static array $fields = ['id'];

	/**
	 * @return void
	 */
	protected function setupJoins(): void
	{
		throw new \RuntimeException('Simulated misconfigured join callback');
	}
}

/**
 * A normal, well-behaved model used to prove a prior exception does
 * not leave $skipJoins stuck true for other model classes.
 *
 * @package Proto\Tests\Unit\Models
 */
final class HealthyJoinModel extends Model
{
	/**
	 * @var string|null
	 */
	protected static ?string $tableName = 'healthy_join_model';

	/**
	 * @var array
	 */
	protected static array $fields = ['id'];
}
