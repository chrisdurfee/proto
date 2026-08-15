<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Auth;

use Proto\Auth\Policies\Policy;
use Proto\Auth\PolicyProxy;
use Proto\Controllers\Controller;
use Proto\Http\Router\Request;
use Proto\Tests\Test;

/**
 * PolicyProxyTest
 *
 * @package Proto\Tests\Unit\Auth
 */
final class PolicyProxyTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * Missing action and missing default() deny.
	 *
	 * @return void
	 */
	public function testMissingActionAndDefaultDenies(): void
	{
		$controller = new class extends Controller
		{
			public function get(Request $request): object
			{
				return (object)['ok' => true];
			}

			public function update(Request $request): object
			{
				return (object)['ok' => true];
			}
		};

		$policy = new class extends Policy
		{
			public function update(Request $request): bool
			{
				return true;
			}
		};

		$proxy = $this->exposingProxy($controller, $policy);
		$this->assertFalse($proxy->exposeCheck('get', [new Request()]));
		$this->assertTrue($proxy->exposeCheck('update', [new Request()]));
	}

	/**
	 * Explicit default() allow covers missing action methods.
	 *
	 * @return void
	 */
	public function testDefaultAllowCoversMissingAction(): void
	{
		$controller = new class extends Controller
		{
			public function get(Request $request): object
			{
				return (object)['ok' => true];
			}
		};

		$policy = new class extends Policy
		{
			public function default(): bool
			{
				return true;
			}
		};

		$proxy = $this->exposingProxy($controller, $policy);
		$this->assertTrue($proxy->exposeCheck('get', [new Request()]));
	}

	/**
	 * @param Controller $controller
	 * @param Policy $policy
	 * @return PolicyProxy
	 */
	private function exposingProxy(Controller $controller, Policy $policy): PolicyProxy
	{
		return new class($controller, $policy) extends PolicyProxy
		{
			public function exposeCheck(string $method, array $arguments): bool
			{
				return $this->checkPolicy($method, $arguments);
			}
		};
	}
}
