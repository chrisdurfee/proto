<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Error;

use Proto\Database\Adapters\Adapter;
use Proto\Database\DatabaseUnavailableException;
use Proto\Database\QueryBuilder\AdapterProxy;
use Proto\Database\QueryBuilder\Select;
use Proto\Error\Error;
use Proto\Tests\Test;
use ReflectionProperty;

/**
 * ServerErrorResponseTest
 *
 * Unhandled failures must produce a generic JSON error body (503 when
 * the database is down), never a blank page, a 200 with no content, or
 * a body that leaks the exception message outside dev.
 *
 * @package Proto\Tests\Unit\Error
 */
final class ServerErrorResponseTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @var bool
	 */
	private bool $savedDisplay = false;

	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->savedDisplay = $this->displayErrors()->getValue();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		$this->displayErrors()->setValue(null, $this->savedDisplay);
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testProductionBodyIsGenericJson(): void
	{
		$this->displayErrors()->setValue(null, false);

		$body = $this->render(new \RuntimeException("SQLSTATE: Access denied for user 'app'@'10.0.0.5'"));
		$decoded = json_decode($body, true);

		$this->assertIsArray($decoded);
		$this->assertFalse($decoded['success']);
		$this->assertStringNotContainsString('Access denied', $body);
		$this->assertStringNotContainsString('RuntimeException', $body);
	}

	/**
	 * @return void
	 */
	public function testDatabaseOutageSaysTemporarilyUnavailable(): void
	{
		$this->displayErrors()->setValue(null, false);

		$body = $this->render(new DatabaseUnavailableException('Database unavailable: refused'));
		$decoded = json_decode($body, true);

		$this->assertStringContainsString('temporarily unavailable', $decoded['message']);
		$this->assertStringNotContainsString('refused', $body);
	}

	/**
	 * @return void
	 */
	public function testDevBodyIncludesDetails(): void
	{
		$this->displayErrors()->setValue(null, true);

		$body = $this->render(new \LogicException('boom'));
		$this->assertStringContainsString('LogicException: boom', $body);
	}

	/**
	 * @return void
	 */
	public function testFetchThrowsTypedExceptionWhenAdapterCannotConnect(): void
	{
		$adapter = $this->getMockBuilder(Adapter::class)
			->disableOriginalConstructor()
			->getMock();
		$adapter->method('fetch')->willReturn(null);
		$adapter->method('getLastError')->willReturn('Connection refused');

		$proxy = new AdapterProxy(new Select('items', 'i'), $adapter);

		$this->expectException(DatabaseUnavailableException::class);
		$proxy->fetch();
	}

	/**
	 * @return void
	 */
	public function testFetchStillReturnsRowsNormally(): void
	{
		$adapter = $this->getMockBuilder(Adapter::class)
			->disableOriginalConstructor()
			->getMock();
		$adapter->method('fetch')->willReturn([]);

		$proxy = new AdapterProxy(new Select('items', 'i'), $adapter);
		$this->assertSame([], $proxy->fetch());
	}

	/**
	 * @param \Throwable $exception
	 * @return string
	 */
	private function render(\Throwable $exception): string
	{
		ob_start();
		HttpErrorProbe::renderServerError($exception);
		return (string)ob_get_clean();
	}

	/**
	 * @return ReflectionProperty
	 */
	private function displayErrors(): ReflectionProperty
	{
		return new ReflectionProperty(Error::class, 'displayErrors');
	}
}

/**
 * Renders as if serving an HTTP request, without touching PHPUnit's
 * own output buffers.
 *
 * @package Proto\Tests\Unit\Error
 */
final class HttpErrorProbe extends Error
{
	protected static function isCli(): bool
	{
		return false;
	}

	protected static function headersSent(): bool
	{
		return false;
	}

	protected static function clearOutputBuffers(): void
	{
	}
}
