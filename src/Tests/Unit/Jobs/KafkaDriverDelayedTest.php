<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Jobs;

use Proto\Jobs\Drivers\KafkaDriver;
use Proto\Tests\Test;

/**
 * KafkaDriverDelayedTest
 *
 * pop() returned null for a message that was not due yet without keeping
 * it anywhere; later commits moved the group offset past it, so delayed
 * jobs were lost. Returning null also ended stop-when-empty runs while
 * ready jobs were still queued behind it.
 *
 * Uses an in-memory topic in place of a broker (rdkafka is not needed);
 * behaviour against a real broker is not covered here.
 *
 * @package Proto\Tests\Unit\Jobs
 */
final class KafkaDriverDelayedTest extends Test
{
	/**
	 * This test does not touch the database.
	 *
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * Defines the rdkafka constants pop() uses when the extension is absent.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();

		foreach ([
			'RD_KAFKA_RESP_ERR_NO_ERROR' => 0,
			'RD_KAFKA_RESP_ERR__PARTITION_EOF' => -191,
			'RD_KAFKA_RESP_ERR__TIMED_OUT' => -185,
		] as $name => $value)
		{
			if (!defined($name))
			{
				define($name, $value);
			}
		}
	}

	/**
	 * Builds a driver over an in-memory topic.
	 *
	 * @param array<int, array> $payloads Messages in topic order
	 * @param bool $publishSucceeds Whether re-publishing works
	 * @return KafkaDriver
	 */
	private function driver(array $payloads, bool $publishSucceeds = true): KafkaDriver
	{
		return new class($payloads, $publishSucceeds) extends KafkaDriver
		{
			/**
			 * @var array<int, object> Messages not yet consumed.
			 */
			public array $topic = [];

			/**
			 * @var array<int, string> Ids of committed messages.
			 */
			public array $committed = [];

			/**
			 * @var array<int, array{payload: array, delay: int}> Re-published messages.
			 */
			public array $published = [];

			/**
			 * @param array $payloads
			 * @param bool $publishSucceeds
			 */
			public function __construct(array $payloads, private bool $publishSucceeds)
			{
				$this->config = ['topic_prefix' => 'test-', 'timeout_ms' => 1];
				foreach ($payloads as $payload)
				{
					$this->topic[] = $this->message($payload);
				}
			}

			/**
			 * @param array $payload
			 * @return object
			 */
			private function message(array $payload): object
			{
				return (object) ['err' => RD_KAFKA_RESP_ERR_NO_ERROR, 'payload' => json_encode($payload)];
			}

			/**
			 * @param string $topicName
			 * @return object|null
			 */
			protected function consumeMessage(string $topicName): ?object
			{
				return array_shift($this->topic);
			}

			/**
			 * @param object $message
			 * @return void
			 */
			protected function commitMessage(object $message): void
			{
				$this->committed[] = json_decode($message->payload, true)['id'];
			}

			/**
			 * Appends to the in-memory topic instead of producing.
			 *
			 * @param array $payload
			 * @param string $queue
			 * @param int $delay
			 * @return bool
			 */
			public function push(array $payload, string $queue = 'default', int $delay = 0): bool
			{
				if (!$this->publishSucceeds)
				{
					return false;
				}

				$this->published[] = ['payload' => $payload, 'delay' => $delay];
				$this->topic[] = $this->message($payload);
				return true;
			}
		};
	}

	/**
	 * A job payload.
	 *
	 * @param string $id
	 * @param int $dueIn Seconds until due (0 = ready)
	 * @return array
	 */
	private function job(string $id, int $dueIn = 0): array
	{
		return [
			'id' => $id,
			'queue' => 'default',
			'job_class' => 'App\\Jobs\\Example',
			'job_name' => 'Example',
			'data' => null,
			'attempts' => 0,
			'available_at' => date('Y-m-d H:i:s', time() + $dueIn),
		];
	}

	/**
	 * A delayed head message is re-published and committed, and the ready
	 * job behind it is returned.
	 *
	 * @return void
	 */
	public function testDelayedHeadIsRepublishedAndReadyJobReturned(): void
	{
		$driver = $this->driver([$this->job('delayed', 600), $this->job('ready')]);

		$job = $driver->pop('default');

		$this->assertSame('ready', $job['id']);
		$this->assertSame(['delayed'], $driver->committed);
		$this->assertCount(1, $driver->published);
		$this->assertSame('delayed', $driver->published[0]['payload']['id']);
		$this->assertEqualsWithDelta(600, $driver->published[0]['delay'], 5);
	}

	/**
	 * With only delayed jobs left, pop() returns null after one pass and
	 * every delayed job is still on the topic exactly once.
	 *
	 * @return void
	 */
	public function testOnlyDelayedJobsReturnsNullWithoutLosingThem(): void
	{
		$driver = $this->driver([$this->job('a', 600), $this->job('b', 600)]);

		$this->assertNull($driver->pop('default'));

		$remaining = array_map(fn(object $m): string => json_decode($m->payload, true)['id'], $driver->topic);
		sort($remaining);
		$this->assertSame(['a', 'b'], $remaining);
	}

	/**
	 * If re-publishing fails the message is not committed.
	 *
	 * @return void
	 */
	public function testFailedRepublishLeavesMessageUncommitted(): void
	{
		$driver = $this->driver([$this->job('delayed', 600)], false);

		$log = tempnam(sys_get_temp_dir(), 'kafka-log');
		$previous = ini_set('error_log', $log);

		try
		{
			$this->assertNull($driver->pop('default'));
		}
		finally
		{
			ini_set('error_log', $previous === false ? '' : $previous);
			@unlink($log);
		}

		$this->assertSame([], $driver->committed);
	}
}
