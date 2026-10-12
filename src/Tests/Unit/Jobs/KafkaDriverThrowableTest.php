<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Jobs;

use Proto\Jobs\Drivers\KafkaDriver;
use Proto\Tests\Test;

/**
 * KafkaDriverThrowableTest
 *
 * KafkaDriver caught only \Exception, so an \Error from the client (or a
 * TypeError from a malformed payload) escaped push()/pop() and killed
 * the worker instead of being logged and reported as a failure.
 *
 * @package Proto\Tests\Unit\Jobs
 */
final class KafkaDriverThrowableTest extends Test
{
	/**
	 * This test does not touch the database.
	 *
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * Builds a driver whose Kafka clients throw \Error, without requiring
	 * the rdkafka extension.
	 *
	 * @return KafkaDriver
	 */
	private function driver(): KafkaDriver
	{
		return new class extends KafkaDriver
		{
			/**
			 * Skips the rdkafka extension check.
			 */
			public function __construct()
			{
				$this->config = ['topic_prefix' => 'test-', 'timeout_ms' => 1];
			}

			/**
			 * @return \RdKafka\Producer
			 */
			protected function getProducer(): \RdKafka\Producer
			{
				throw new \Error('producer exploded');
			}

			/**
			 * @return \RdKafka\KafkaConsumer
			 */
			protected function getConsumer(): \RdKafka\KafkaConsumer
			{
				throw new \Error('consumer exploded');
			}
		};
	}

	/**
	 * Runs a callback with error_log redirected to a temp file.
	 *
	 * @param callable $callback
	 * @return string The logged output
	 */
	private function captureLog(callable $callback): string
	{
		$log = tempnam(sys_get_temp_dir(), 'kafka-log');
		$previous = ini_set('error_log', $log);

		try
		{
			$callback();
			return (string) file_get_contents($log);
		}
		finally
		{
			ini_set('error_log', $previous === false ? '' : $previous);
			@unlink($log);
		}
	}

	/**
	 * push() reports failure instead of throwing.
	 *
	 * @return void
	 */
	public function testPushReturnsFalseOnError(): void
	{
		$driver = $this->driver();
		$logged = $this->captureLog(function () use ($driver): void
		{
			$this->assertFalse($driver->push(['id' => 'job_1', 'data' => null]));
		});

		$this->assertStringContainsString('producer exploded', $logged);
	}

	/**
	 * pop() reports an empty result instead of throwing.
	 *
	 * @return void
	 */
	public function testPopReturnsNullOnError(): void
	{
		$driver = $this->driver();
		$logged = $this->captureLog(function () use ($driver): void
		{
			$this->assertNull($driver->pop('default'));
		});

		$this->assertStringContainsString('consumer exploded', $logged);
	}
}
