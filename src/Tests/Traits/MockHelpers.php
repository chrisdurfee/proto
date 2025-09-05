<?php declare(strict_types=1);
namespace Proto\Tests\Traits;

/**
 * MockHelpers
 *
 * Provides mocking utilities for test cases.
 *
 * @package Proto\Tests\Traits
 */
trait MockHelpers
{
	/**
	 * @var array $mocks Store created mocks for cleanup
	 */
	protected array $mocks = [];

	/**
	 * Creates a mock of a service class.
	 *
	 * @param string $serviceClass
	 * @param array $methods Methods to mock (empty for all)
	 * @return \PHPUnit\Framework\MockObject\MockObject
	 */
	protected function mockService(string $serviceClass, array $methods = []): \PHPUnit\Framework\MockObject\MockObject
	{
		$mock = $this->createMock($serviceClass);
		$this->mocks[] = $mock;
		return $mock;
	}

	/**
	 * Creates a spy of a service class.
	 *
	 * @param string $serviceClass
	 * @param array $methods Methods to spy on (empty for all)
	 * @return \PHPUnit\Framework\MockObject\MockObject
	 */
	protected function spyService(string $serviceClass, array $methods = []): \PHPUnit\Framework\MockObject\MockObject
	{
		$spy = $this->createPartialMock($serviceClass, $methods);
		$this->mocks[] = $spy;
		return $spy;
	}

	/**
	 * Creates a partial mock with specific methods.
	 *
	 * @param string $class
	 * @param array $methods
	 * @return \PHPUnit\Framework\MockObject\MockObject
	 */
	protected function partialMock(string $class, array $methods = []): \PHPUnit\Framework\MockObject\MockObject
	{
		$mock = $this->createPartialMock($class, $methods);
		$this->mocks[] = $mock;
		return $mock;
	}

	/**
	 * Creates a stub that returns specific values.
	 *
	 * @param string $class
	 * @param array $methodReturns Array of method => return value
	 * @return \PHPUnit\Framework\MockObject\MockObject
	 */
	protected function createStub(string $class, array $methodReturns = []): \PHPUnit\Framework\MockObject\MockObject
	{
		$stub = $this->createMock($class);

		foreach ($methodReturns as $method => $returnValue) {
			$stub->method($method)->willReturn($returnValue);
		}

		$this->mocks[] = $stub;
		return $stub;
	}

	/**
	 * Expects a method to be called with specific arguments.
	 *
	 * @param \PHPUnit\Framework\MockObject\MockObject $mock
	 * @param string $method
	 * @param array $arguments
	 * @param mixed $returnValue
	 * @param int $times
	 * @return void
	 */
	protected function expectMethodCall(
		\PHPUnit\Framework\MockObject\MockObject $mock,
		string $method,
		array $arguments = [],
		mixed $returnValue = null,
		int $times = 1
	): void {
		$expectation = $mock->expects($this->exactly($times))->method($method);

		if (!empty($arguments))
		{
			$expectation->with(...$arguments);
		}

		if ($returnValue !== null)
		{
			$expectation->willReturn($returnValue);
		}
	}

	/**
	 * Expects a method to never be called.
	 *
	 * @param \PHPUnit\Framework\MockObject\MockObject $mock
	 * @param string $method
	 * @return void
	 */
	protected function expectMethodNeverCalled(\PHPUnit\Framework\MockObject\MockObject $mock, string $method): void
	{
		$mock->expects($this->never())->method($method);
	}

	/**
	 * Creates a mock with fluent expectations.
	 *
	 * @param string $class
	 * @param callable $expectations
	 * @return \PHPUnit\Framework\MockObject\MockObject
	 */
	protected function mockWithExpectations(string $class, callable $expectations): \PHPUnit\Framework\MockObject\MockObject
	{
		$mock = $this->createMock($class);
		$expectations($mock, $this);
		$this->mocks[] = $mock;
		return $mock;
	}

	/**
	 * Verifies that all mock expectations were met.
	 *
	 * @return void
	 */
	protected function verifyMockExpectations(): void
	{
		foreach ($this->mocks as $mock)
		{
			// PHPUnit automatically verifies expectations in tearDown
			// This method is for explicit verification if needed
		}
	}

	/**
	 * Clears all created mocks.
	 *
	 * @return void
	 */
	protected function clearMocks(): void
	{
		$this->mocks = [];
	}
}