<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Module;

use Proto\Module\Gateway;
use Proto\Module\Traits\LazyGatewayTrait;
use Proto\Tests\Test;

/**
 * GatewayTest
 *
 * @package Proto\Tests\Unit\Module
 */
final class GatewayTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testGatewayMemoizesChildInstances(): void
	{
		$gateway = new class extends Gateway
		{
			protected function model(): string
			{
				return \stdClass::class;
			}

			public function child(): object
			{
				return $this->gateway(\stdClass::class);
			}
		};

		$first = $gateway->child();
		$second = $gateway->child();
		$this->assertSame($first, $second);
	}

	/**
	 * @return void
	 */
	public function testGatewayMemoizesByConstructorArgs(): void
	{
		$gateway = new class extends Gateway
		{
			protected function model(): string
			{
				return \stdClass::class;
			}

			public function child(int $id): object
			{
				return $this->gateway(GatewayArgProbe::class, $id);
			}
		};

		$first = $gateway->child(1);
		$again = $gateway->child(1);
		$other = $gateway->child(2);
		$this->assertSame($first, $again);
		$this->assertNotSame($first, $other);
		$this->assertEquals(1, $first->id);
		$this->assertEquals(2, $other->id);
	}

	/**
	 * Facade gateways with no single primary model can compose
	 * LazyGatewayTrait directly instead of extending Gateway (which
	 * requires an abstract model()).
	 *
	 * @return void
	 */
	public function testLazyGatewayTraitWorksWithoutExtendingGateway(): void
	{
		$facade = new class
		{
			use LazyGatewayTrait;

			public function child(): object
			{
				return $this->gateway(\stdClass::class);
			}

			public function scoped(int $id): object
			{
				return $this->gateway(GatewayArgProbe::class, $id);
			}
		};

		$this->assertSame($facade->child(), $facade->child());
		$this->assertSame($facade->scoped(1), $facade->scoped(1));
		$this->assertNotSame($facade->scoped(1), $facade->scoped(2));
	}
}

/**
 * @package Proto\Tests\Unit\Module
 */
final class GatewayArgProbe
{
	public function __construct(public int $id)
	{
	}
}
