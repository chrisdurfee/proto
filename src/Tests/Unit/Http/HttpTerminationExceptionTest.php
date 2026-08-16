<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Http;

use Proto\Auth\Policies\Policy;
use Proto\Auth\PolicyProxy;
use Proto\Controllers\ApiController;
use Proto\Controllers\Controller;
use Proto\Http\HttpTerminationException;
use Proto\Http\Router\Request;
use Proto\Tests\Test;

/**
 * HttpTerminationExceptionTest
 *
 * Verifies the `die`/`exit` → {@see HttpTerminationException} refactor:
 * validation/policy/rate-limit failures throw instead of terminating
 * the PHP process directly, so the error path is unit-testable and
 * `finally`/rollback blocks around the call site still run.
 *
 * @package Proto\Tests\Unit\Http
 */
final class HttpTerminationExceptionTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * setError() throws HttpTerminationException carrying the same
	 * status code and an error payload, instead of calling `die`.
	 *
	 * @return void
	 */
	public function testSetErrorThrowsInsteadOfDying(): void
	{
		$controller = $this->exposingController();

		try
		{
			$controller->exposeSetError('Nope', 404);
			$this->fail('Expected HttpTerminationException to be thrown.');
		}
		catch (HttpTerminationException $exception)
		{
			$this->assertSame(404, $exception->getStatusCode());
			$payload = $exception->getPayload();
			$this->assertFalse($payload->success);
			$this->assertSame('Nope', $payload->message);
		}
	}

	/**
	 * validateRules() with failing rules throws a 422
	 * HttpTerminationException carrying field-level errors, instead of
	 * making the `bool` return meaningful/reachable.
	 *
	 * @return void
	 */
	public function testValidateRulesThrowsOn422ForFailingRules(): void
	{
		$controller = $this->exposingController();

		try
		{
			$controller->exposeValidateRules((object)['email' => ''], ['email' => 'required']);
			$this->fail('Expected HttpTerminationException to be thrown.');
		}
		catch (HttpTerminationException $exception)
		{
			$this->assertSame(422, $exception->getStatusCode());
			$payload = $exception->getPayload();
			$this->assertFalse($payload->success);
		}
	}

	/**
	 * validateRules() with passing rules (or no rules) still returns
	 * true and does not throw — the non-error path is unchanged.
	 *
	 * @return void
	 */
	public function testValidateRulesReturnsTrueWhenValid(): void
	{
		$controller = $this->exposingController();
		$this->assertTrue($controller->exposeValidateRules((object)[], []));
	}

	/**
	 * A `finally` block around a call that used to `die` now correctly
	 * runs, because the failure path throws instead of terminating the
	 * process directly.
	 *
	 * @return void
	 */
	public function testFinallyBlockRunsAfterTermination(): void
	{
		$controller = $this->exposingController();
		$finallyRan = false;

		try
		{
			try
			{
				$controller->exposeSetError('boom', 500);
			}
			finally
			{
				$finallyRan = true;
			}

			$this->fail('Expected HttpTerminationException to propagate through the finally block.');
		}
		catch (HttpTerminationException $exception)
		{
			$this->assertSame(500, $exception->getStatusCode());
		}

		$this->assertTrue($finallyRan, 'finally block must run even though the call terminates the request.');
	}

	/**
	 * PolicyProxy denies by throwing HttpTerminationException(403)
	 * instead of calling `exit` directly.
	 *
	 * @return void
	 */
	public function testPolicyProxyDenialThrowsInsteadOfExiting(): void
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
				return false;
			}
		};

		$proxy = new PolicyProxy($controller, $policy);

		try
		{
			$proxy->get(new Request());
			$this->fail('Expected HttpTerminationException to be thrown.');
		}
		catch (HttpTerminationException $exception)
		{
			$this->assertSame(403, $exception->getStatusCode());
		}
	}

	/**
	 * @return ExposingApiController
	 */
	private function exposingController(): ExposingApiController
	{
		return new ExposingApiController();
	}
}

/**
 * @package Proto\Tests\Unit\Http
 */
final class ExposingApiController extends ApiController
{
	/**
	 * @param string|null $message
	 * @param int $code
	 * @return never
	 */
	public function exposeSetError(?string $message = null, int $code = 400): never
	{
		$this->setError($message, $code);
	}

	/**
	 * @param object|array $data
	 * @param array $rules
	 * @return bool
	 */
	public function exposeValidateRules(object|array $data, array $rules = []): bool
	{
		return $this->validateRules($data, $rules);
	}
}
