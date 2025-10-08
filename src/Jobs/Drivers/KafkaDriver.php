<?php declare(strict_types=1);
namespace Proto\Jobs\Drivers;

use Proto\Base;
use RdKafka\Conf;
use RdKafka\Producer;
use RdKafka\Consumer;
use RdKafka\TopicConf;
use RdKafka\KafkaConsumer;
use RdKafka\Message;

/**
 * KafkaDriver
 *
 * Kafka driver for job queue storage using Apache Kafka.
 * Requires the rdkafka PHP extension: https://github.com/arnaud-lb/php-rdkafka
 *
 * @package Proto\Jobs\Drivers
 */
class KafkaDriver extends Base implements DriverInterface
{
	/**
	 * @var array $config Driver configuration
	 */
	protected array $config;

	/**
     * @suppresswarnings PHP0413
	 * @var Producer|null $producer Kafka producer
	 */
	protected ?Producer $producer = null;

	/**
     * @suppresswarnings PHP0413
	 * @var KafkaConsumer|null $consumer Kafka consumer
	 */
	protected ?KafkaConsumer $consumer = null;

	/**
	 * @var array $processingJobs Jobs currently being processed (in-memory)
	 */
	protected array $processingJobs = [];

	/**
	 * @var array $failedJobs Failed jobs (in-memory)
	 */
	protected array $failedJobs = [];

	/**
	 * @var array $completedJobs Completed jobs (in-memory for stats)
	 */
	protected array $completedJobs = [];

	/**
	 * Constructor.
	 *
	 * @param array $config Driver configuration
	 */
	public function __construct(array $config = [])
	{
		parent::__construct();

		$this->config = array_merge([
			'brokers' => 'localhost:9092',
			'group_id' => 'proto-jobs-consumer',
			'topic_prefix' => 'proto-jobs-',
			'auto_offset_reset' => 'earliest',
			'enable_auto_commit' => false,
			'compression' => 'snappy',
			'batch_size' => 10000,
			'timeout_ms' => 1000,
		], $config);

		if (!extension_loaded('rdkafka'))
        {
			throw new \RuntimeException('The rdkafka extension is required to use KafkaDriver. Install it with: pecl install rdkafka');
		}
	}

	/**
	 * Get or create Kafka producer.
	 *
     * @suppresswarnings PHP0413
	 * @return Producer
	 */
	protected function getProducer(): Producer
	{
		if ($this->producer === null)
        {
			$conf = new Conf();
			$conf->set('metadata.broker.list', $this->config['brokers']);
			$conf->set('compression.codec', $this->config['compression']);

			// Set delivery report callback (optional)
			$conf->setDrMsgCb(function ($kafka, $message) {
				if ($message->err) {
					error_log("Kafka message delivery failed: " . $message->errstr());
				}
			});

			$this->producer = new Producer($conf);
		}

		return $this->producer;
	}

	/**
	 * Get or create Kafka consumer.
	 *
     * @suppresswarnings PHP0413
	 * @return KafkaConsumer
	 */
	protected function getConsumer(): KafkaConsumer
	{
		if ($this->consumer === null)
        {
			$conf = new Conf();
			$conf->set('metadata.broker.list', $this->config['brokers']);
			$conf->set('group.id', $this->config['group_id']);
			$conf->set('auto.offset.reset', $this->config['auto_offset_reset']);
			$conf->set('enable.auto.commit', $this->config['enable_auto_commit'] ? 'true' : 'false');

			$this->consumer = new KafkaConsumer($conf);
		}

		return $this->consumer;
	}

	/**
	 * Get Kafka topic name for queue.
	 *
	 * @param string $queue Queue name
	 * @return string
	 */
	protected function getTopicName(string $queue): string
	{
		return $this->config['topic_prefix'] . $queue;
	}

	/**
	 * Push a job onto the queue.
	 *
	 * @param array $payload Job payload
	 * @param string $queue Queue name
	 * @param int $delay Delay in seconds
     * @suppresswarnings PHP0415
	 * @return bool
	 */
	public function push(array $payload, string $queue = 'default', int $delay = 0): bool
	{
		try {
			$producer = $this->getProducer();
			$topicName = $this->getTopicName($queue);

			$topic = $producer->newTopic($topicName);

			// Add delay timestamp if needed
			if ($delay > 0)
            {
				$payload['available_at'] = date('Y-m-d H:i:s', time() + $delay);
			}
            else
            {
				$payload['available_at'] = date('Y-m-d H:i:s');
			}

			$payload['status'] = 'pending';

			// Produce message
			$topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($payload));

			// Poll for events (this allows callbacks to be triggered)
			$producer->poll(0);

			// Flush messages to ensure delivery
			$result = $producer->flush(10000); // 10 second timeout

			return $result === RD_KAFKA_RESP_ERR_NO_ERROR;

		}
        catch (\Exception $e)
        {
			error_log("Kafka push error: " . $e->getMessage());
			return false;
		}
	}

	/**
	 * Pop a job from the queue.
	 *
	 * @param string $queue Queue name
     * @suppresswarnings PHP0415
	 * @return array|null
	 */
	public function pop(string $queue = 'default'): ?array
	{
		try {
			$consumer = $this->getConsumer();
			$topicName = $this->getTopicName($queue);

			// Subscribe to topic if not already subscribed
			if (empty($consumer->getSubscription()))
            {
				$consumer->subscribe([$topicName]);
			}

			// Consume message
			$message = $consumer->consume($this->config['timeout_ms']);

			if ($message === null)
            {
				return null;
			}

			switch ($message->err)
            {
				case RD_KAFKA_RESP_ERR_NO_ERROR:
					$payload = json_decode($message->payload, true);

					if (!$payload)
                    {
						error_log("Invalid JSON payload in Kafka message");
						$consumer->commit($message);
						return null;
					}

					// Check if job is delayed
					$availableAt = strtotime($payload['available_at'] ?? 'now');
					if ($availableAt > time())
                    {
						// Job is not ready yet, skip it
						// Note: This is a limitation of Kafka - delayed jobs aren't perfectly supported
						// Consider using a separate delayed jobs topic or database for delayed jobs
						return null;
					}

					// Mark as processing
					$payload['status'] = 'processing';
					$payload['reserved_at'] = date('Y-m-d H:i:s');

					// Store in processing jobs (for tracking)
					$this->processingJobs[$payload['id']] = [
						'payload' => $payload,
						'message' => $message,
					];

					return $payload;

				case RD_KAFKA_RESP_ERR__PARTITION_EOF:
					// No more messages
					return null;

				case RD_KAFKA_RESP_ERR__TIMED_OUT:
					// Timeout
					return null;

				default:
					error_log("Kafka consume error: " . $message->errstr());
					return null;
			}

		}
        catch (\Exception $e)
        {
			error_log("Kafka pop error: " . $e->getMessage());
			return null;
		}
	}

	/**
	 * Mark a job as completed.
	 *
	 * @param string $jobId Job ID
	 * @return bool
	 */
	public function markCompleted(string $jobId): bool
	{
		try {
			if (!isset($this->processingJobs[$jobId]))
            {
				return false;
			}

			$jobData = $this->processingJobs[$jobId];

			// Commit the message to Kafka
			$consumer = $this->getConsumer();
			$consumer->commit($jobData['message']);

			// Track completed job for stats
			$this->completedJobs[$jobId] = [
				'completed_at' => date('Y-m-d H:i:s'),
				'payload' => $jobData['payload'],
			];

			// Remove from processing
			unset($this->processingJobs[$jobId]);

			return true;

		}
        catch (\Exception $e)
        {
			error_log("Kafka mark completed error: " . $e->getMessage());
			return false;
		}
	}

	/**
	 * Mark a job as failed.
	 *
	 * @param string $jobId Job ID
	 * @param string $error Error message
	 * @return bool
	 */
	public function markFailed(string $jobId, string $error): bool
	{
		try {
			if (!isset($this->processingJobs[$jobId]))
            {
				return false;
			}

			$jobData = $this->processingJobs[$jobId];
			$payload = $jobData['payload'];

			// Commit the original message
			$consumer = $this->getConsumer();
			$consumer->commit($jobData['message']);

			// Store in failed jobs
			$failedJob = [
				'id' => uniqid('failed_', true),
				'job_id' => $jobId,
				'queue' => $payload['queue'] ?? 'default',
				'job_class' => $payload['job_class'],
				'job_name' => $payload['job_name'],
				'data' => json_encode($payload['data']),
				'attempts' => $payload['attempts'],
				'error' => $error,
				'failed_at' => date('Y-m-d H:i:s'),
			];

			$this->failedJobs[$jobId] = $failedJob;

			// Optionally publish to a dead-letter queue
			$this->publishToDeadLetterQueue($failedJob);

			// Remove from processing
			unset($this->processingJobs[$jobId]);

			return true;

		}
        catch (\Exception $e)
        {
			error_log("Kafka mark failed error: " . $e->getMessage());
			return false;
		}
	}

	/**
	 * Publish failed job to dead-letter queue.
	 *
	 * @param array $failedJob Failed job data
     * @suppresswarnings PHP0415
	 * @return void
	 */
	protected function publishToDeadLetterQueue(array $failedJob): void
	{
		try {
			$producer = $this->getProducer();
			$topic = $producer->newTopic($this->config['topic_prefix'] . 'dead-letter');

			$topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($failedJob));
			$producer->poll(0);
			$producer->flush(5000);

		}
        catch (\Exception $e)
        {
			error_log("Failed to publish to dead-letter queue: " . $e->getMessage());
		}
	}

	/**
	 * Retry a job.
	 *
	 * @param string $jobId Job ID
	 * @param int $attempts Current attempt count
	 * @param int $delay Delay before retry in seconds
	 * @return bool
	 */
	public function retry(string $jobId, int $attempts, int $delay): bool
	{
		try {
			if (!isset($this->processingJobs[$jobId]))
            {
				return false;
			}

			$jobData = $this->processingJobs[$jobId];
			$payload = $jobData['payload'];

			// Update payload for retry
			$payload['attempts'] = $attempts;
			$payload['status'] = 'pending';
			$payload['available_at'] = date('Y-m-d H:i:s', time() + $delay);
			$payload['reserved_at'] = null;

			// Commit the original message
			$consumer = $this->getConsumer();
			$consumer->commit($jobData['message']);

			// Re-queue the job
			$result = $this->push($payload, $payload['queue'] ?? 'default', $delay);

			// Remove from processing
			unset($this->processingJobs[$jobId]);

			return $result;

		}
        catch (\Exception $e)
        {
			error_log("Kafka retry error: " . $e->getMessage());
			return false;
		}
	}

	/**
	 * Get queue statistics.
	 *
	 * @param string|null $queue Queue name (null for all queues)
	 * @return array
	 */
	public function getStats(?string $queue = null): array
	{
		// Note: Kafka doesn't provide easy access to message counts without consuming
		// This is a limitation - for production use, consider maintaining stats in a database
		return [
			'pending' => 0, // Would need Kafka admin API or consumer lag monitoring
			'processing' => count($this->processingJobs),
			'completed' => count($this->completedJobs),
			'failed' => count($this->failedJobs),
			'total' => count($this->processingJobs) + count($this->completedJobs) + count($this->failedJobs),
			'failed_total' => count($this->failedJobs),
			'note' => 'Kafka stats are limited. Consider using Kafka monitoring tools for accurate queue depth.',
		];
	}

	/**
	 * Clear all jobs from a queue.
	 *
	 * @param string $queue Queue name
	 * @return bool
	 */
	public function clear(string $queue = 'default'): bool
	{
		// Note: Clearing a Kafka topic requires admin privileges
		// This is a simplified implementation that just unsubscribes
		try {
			if ($this->consumer !== null)
            {
				$this->consumer->unsubscribe();
			}

			// Clear in-memory data
			$this->processingJobs = [];
			$this->completedJobs = [];

			// Note: This doesn't actually delete messages from Kafka
			// You would need to use Kafka admin tools or set retention policies
			error_log("Warning: KafkaDriver::clear() only clears local state. Use Kafka admin tools to delete topic messages.");

			return true;

		}
        catch (\Exception $e)
        {
			error_log("Kafka clear error: " . $e->getMessage());
			return false;
		}
	}

	/**
	 * Get failed jobs.
	 *
	 * @param int $limit Number of failed jobs to retrieve
	 * @param int $offset Offset for pagination
	 * @return array
	 */
	public function getFailedJobs(int $limit = 50, int $offset = 0): array
	{
		// Return from in-memory store
		$failedJobs = array_values($this->failedJobs);
		return array_slice($failedJobs, $offset, $limit);
	}

	/**
	 * Retry a failed job.
	 *
	 * @param string $jobId Job ID
	 * @return bool
	 */
	public function retryFailedJob(string $jobId): bool
	{
		try {
			if (!isset($this->failedJobs[$jobId]))
            {
				return false;
			}

			$failedJob = $this->failedJobs[$jobId];

			// Create new job payload
			$payload = [
				'id' => uniqid('job_', true),
				'queue' => $failedJob['queue'],
				'job_class' => $failedJob['job_class'],
				'job_name' => $failedJob['job_name'],
				'data' => json_decode($failedJob['data'], true),
				'attempts' => 0,
				'max_retries' => 3,
				'timeout' => 300,
				'created_at' => date('Y-m-d H:i:s'),
			];

			// Re-queue the job
			$result = $this->push($payload, $failedJob['queue'], 0);

			if ($result)
            {
				// Remove from failed jobs
				unset($this->failedJobs[$jobId]);
			}

			return $result;

		}
        catch (\Exception $e)
        {
			error_log("Kafka retry failed job error: " . $e->getMessage());
			return false;
		}
	}

	/**
	 * Close connections and cleanup.
	 *
	 * @return void
	 */
	public function __destruct()
	{
		if ($this->consumer !== null)
        {
			try
            {
				$this->consumer->close();
			}
            catch (\Exception $e)
            {
				error_log("Error closing Kafka consumer: " . $e->getMessage());
			}
		}
	}
}
