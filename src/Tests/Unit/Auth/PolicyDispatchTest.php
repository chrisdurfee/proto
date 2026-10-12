<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Auth;

use Proto\Auth\Policies\Policy;
use Proto\Auth\PolicyProxy;
use Proto\Controllers\Controller;
use Proto\Http\HttpTerminationException;
use Proto\Http\Router\Request;
use Proto\Tests\Test;

/**
 * PolicyDispatchTest
 *
 * End-to-end before / action / default / after dispatch through
 * PolicyProxy::__call(). A denial must surface as a 403
 * HttpTerminationException rather than reaching the controller.
 *
 * @package Proto\Tests\Unit\Auth
 */
final class PolicyDispatchTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return Controller
	 */
	private function controller(): Controller
	{
		return new class extends Controller
		{
			public array $calls = [];

			public function get(Request $request): object
			{
				$this->calls[] = 'get';
				return (object)['id' => 1, 'ownerId' => 9];
			}

			public function delete(Request $request): object
			{
				$this->calls[] = 'delete';
				return (object)['deleted' => true];
			}
		};
	}

	/**
	 * @param callable $call
	 * @return HttpTerminationException
	 */
	private function expectDenied(callable $call): HttpTerminationException
	{
		try
		{
			$call();
		}
		catch (HttpTerminationException $e)
		{
			$this->assertSame(403, $e->getStatusCode());
			return $e;
		}

		$this->fail('Expected the policy to deny the call.');
	}

	/**
	 * @return void
	 */
	public function testAllowedActionReachesController(): void
	{
		$controller = $this->controller();
		$proxy = new PolicyProxy($controller, new class extends Policy
		{
			public function get(Request $request): bool
			{
				return true;
			}
		});

		$result = $proxy->get(new Request());
		$this->assertSame(1, $result->id);
		$this->assertSame(['get'], $controller->calls);
	}

	/**
	 * @return void
	 */
	public function testMissingActionWithoutDefaultDeniesBeforeController(): void
	{
		$controller = $this->controller();
		$proxy = new PolicyProxy($controller, new class extends Policy {});

		$error = $this->expectDenied(fn() => $proxy->delete(new Request()));
		$this->assertSame([], $controller->calls);
		$this->assertStringContainsString('policy is blocking', json_encode($error->getPayload()));
	}

	/**
	 * @return void
	 */
	public function testDefaultDenyIsHonoured(): void
	{
		$controller = $this->controller();
		$proxy = new PolicyProxy($controller, new class extends Policy
		{
			public function default(): bool
			{
				return false;
			}
		});

		$this->expectDenied(fn() => $proxy->get(new Request()));
		$this->assertSame([], $controller->calls);
	}

	/**
	 * @return void
	 */
	public function testExplicitActionOverridesDefault(): void
	{
		$proxy = new PolicyProxy($this->controller(), new class extends Policy
		{
			public function default(): bool
			{
				return true;
			}

			public function delete(Request $request): bool
			{
				return false;
			}
		});

		$this->assertSame(1, $proxy->get(new Request())->id);
		$this->expectDenied(fn() => $proxy->delete(new Request()));
	}

	/**
	 * @return void
	 */
	public function testBeforeTrueShortCircuitsActionCheck(): void
	{
		$policy = new class extends Policy
		{
			public int $actionCalls = 0;

			public function before(Request $request): bool
			{
				return true;
			}

			public function delete(Request $request): bool
			{
				$this->actionCalls++;
				return false;
			}
		};

		$proxy = new PolicyProxy($this->controller(), $policy);
		$this->assertTrue($proxy->delete(new Request())->deleted);
		$this->assertSame(0, $policy->actionCalls);
	}

	/**
	 * before() returning false is "no opinion", not a hard deny.
	 *
	 * @return void
	 */
	public function testBeforeFalseFallsThroughToAction(): void
	{
		$proxy = new PolicyProxy($this->controller(), new class extends Policy
		{
			public function before(Request $request): bool
			{
				return false;
			}

			public function get(Request $request): bool
			{
				return true;
			}
		});

		$this->assertSame(1, $proxy->get(new Request())->id);
	}

	/**
	 * @return void
	 */
	public function testActionReceivesTheRequest(): void
	{
		$proxy = new PolicyProxy($this->controller(), new class extends Policy
		{
			public function get(Request $request): bool
			{
				return ($request->params()->id ?? null) === '1';
			}
		});

		$this->assertSame(1, $proxy->get(new Request((object)['id' => '1']))->id);
		$this->expectDenied(fn() => $proxy->get(new Request((object)['id' => '2'])));
	}

	/**
	 * @return void
	 */
	public function testCustomAfterCanRejectResult(): void
	{
		$controller = $this->controller();
		$policy = new class extends Policy
		{
			public mixed $seen = null;

			public function get(Request $request): bool
			{
				return true;
			}

			public function afterGet(mixed $result): bool
			{
				$this->seen = $result;
				return ($result->ownerId ?? null) === 1;
			}
		};

		$proxy = new PolicyProxy($controller, $policy);
		$this->expectDenied(fn() => $proxy->get(new Request()));
		$this->assertSame(['get'], $controller->calls, 'after* runs once the controller has produced a result.');
		$this->assertSame(9, $policy->seen->ownerId);
	}

	/**
	 * @return void
	 */
	public function testGenericAfterCanRejectResult(): void
	{
		$proxy = new PolicyProxy($this->controller(), new class extends Policy
		{
			public function default(): bool
			{
				return true;
			}

			public function after(mixed $result): bool
			{
				return !isset($result->deleted);
			}
		});

		$this->assertSame(1, $proxy->get(new Request())->id);
		$this->expectDenied(fn() => $proxy->delete(new Request()));
	}

	/**
	 * @return void
	 */
	public function testUnknownControllerMethodReturnsNullWithoutPolicy(): void
	{
		$policy = new class extends Policy
		{
			public int $calls = 0;

			public function default(): bool
			{
				$this->calls++;
				return true;
			}
		};

		$proxy = new PolicyProxy($this->controller(), $policy);
		$this->assertNull($proxy->doesNotExist(new Request()));
		$this->assertSame(0, $policy->calls);
	}

	/**
	 * Protected policy helpers are not callable from the proxy, so an
	 * action named after one falls back to default() instead of being
	 * dispatched to the helper.
	 *
	 * @return void
	 */
	public function testProtectedHelperIsNotTreatedAsAction(): void
	{
		$controller = new class extends Controller
		{
			public function hasRole(Request $request): object
			{
				return (object)['ran' => true];
			}
		};

		$proxy = new PolicyProxy($controller, new class extends Policy {});
		$this->expectDenied(fn() => $proxy->hasRole(new Request()));
	}
}
