# Kafka Driver Documentation

The Kafka driver enables the Proto Jobs system to use Apache Kafka as the message broker for job queuing and processing. This provides distributed, fault-tolerant, and scalable job processing capabilities.

## Features

- **Distributed Processing**: Jobs can be processed by multiple workers across different servers
- **High Throughput**: Kafka's high-performance message handling
- **Fault Tolerance**: Messages are persisted and replicated across Kafka brokers
- **Scalability**: Easily scale workers and partitions
- **Dead Letter Queue**: Failed jobs are automatically sent to a dead-letter topic

## Requirements

### PHP Extension

The Kafka driver requires the `rdkafka` PHP extension:

```bash
# Install via PECL
pecl install rdkafka

# Or on Ubuntu/Debian
apt-get install php-rdkafka

# Enable in php.ini
extension=rdkafka.so
```

### Kafka Server

You need a running Kafka cluster. For development, you can use Docker:

```bash
# docker-compose.yml
version: '3'
services:
  zookeeper:
    image: confluentinc/cp-zookeeper:latest
    environment:
      ZOOKEEPER_CLIENT_PORT: 2181
      ZOOKEEPER_TICK_TIME: 2000

  kafka:
    image: confluentinc/cp-kafka:latest
    depends_on:
      - zookeeper
    ports:
      - "9092:9092"
    environment:
      KAFKA_BROKER_ID: 1
      KAFKA_ZOOKEEPER_CONNECT: zookeeper:2181
      KAFKA_ADVERTISED_LISTENERS: PLAINTEXT://localhost:9092
      KAFKA_OFFSETS_TOPIC_REPLICATION_FACTOR: 1
```

```bash
docker-compose up -d
```

## Configuration

### Basic Configuration

```php
use Proto\Jobs\Jobs;

// Configure Jobs to use Kafka
Jobs::configure([
    'driver' => 'kafka',
    'brokers' => 'localhost:9092',
    'group_id' => 'my-app-jobs-consumer',
    'topic_prefix' => 'my-app-jobs-',
]);
```

### Advanced Configuration

```php
Jobs::configure([
    'driver' => 'kafka',

    // Kafka brokers (comma-separated list)
    'brokers' => 'kafka1:9092,kafka2:9092,kafka3:9092',

    // Consumer group ID (workers with same group_id share load)
    'group_id' => 'my-app-jobs-consumer',

    // Topic prefix for job queues
    'topic_prefix' => 'my-app-jobs-',

    // Where to start consuming if no offset is stored
    // Options: 'earliest', 'latest'
    'auto_offset_reset' => 'earliest',

    // Whether to automatically commit offsets
    'enable_auto_commit' => false,

    // Compression type for messages
    // Options: 'none', 'gzip', 'snappy', 'lz4', 'zstd'
    'compression' => 'snappy',

    // Max messages to batch
    'batch_size' => 10000,

    // Consumer timeout in milliseconds
    'timeout_ms' => 1000,
]);
```

## Usage

### Dispatching Jobs

The usage is identical to the database driver:

```php
use Proto\Jobs\Jobs;
use App\Jobs\SendEmailJob;

// Dispatch a job
Jobs::dispatch(SendEmailJob::class, [
    'to' => 'user@example.com',
    'subject' => 'Welcome!',
    'body' => 'Welcome to our service!'
]);

// Dispatch to a specific queue
Jobs::dispatch(SendEmailJob::class, $data, 'emails');

// Dispatch with delay (Note: See limitations below)
Jobs::dispatchLater(300, SendEmailJob::class, $data);
```

### Processing Jobs

```php
// Process jobs from the 'default' queue
Jobs::work('default');

// Process jobs from the 'emails' queue
Jobs::work('emails');

// Process a specific number of jobs then stop
Jobs::work('emails', 10);
```

### Worker Script

Create a worker script (e.g., `kafka_worker.php`):

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Proto\Jobs\Jobs;

// Configure
Jobs::configure([
    'driver' => 'kafka',
    'brokers' => getenv('KAFKA_BROKERS') ?: 'localhost:9092',
    'group_id' => 'my-app-workers',
]);

// Handle graceful shutdown
pcntl_signal(SIGTERM, function() {
    Jobs::stop();
    exit(0);
});

pcntl_signal(SIGINT, function() {
    Jobs::stop();
    exit(0);
});

// Get queue name from command line
$queue = $argv[1] ?? 'default';

echo "Starting Kafka worker for queue: {$queue}\n";

// Process jobs indefinitely
Jobs::work($queue);
```

Run multiple workers:

```bash
# Terminal 1
php kafka_worker.php default

# Terminal 2
php kafka_worker.php emails

# Terminal 3
php kafka_worker.php default  # Load balancing with Terminal 1
```

## Kafka Topics

The driver automatically creates topics based on queue names:

- Queue `default` → Topic `{topic_prefix}default` (e.g., `my-app-jobs-default`)
- Queue `emails` → Topic `{topic_prefix}emails` (e.g., `my-app-jobs-emails`)
- Failed jobs → Topic `{topic_prefix}dead-letter` (e.g., `my-app-jobs-dead-letter`)

## Scaling

### Horizontal Scaling

Run multiple workers with the same `group_id`:

```bash
# Server 1
php kafka_worker.php emails

# Server 2
php kafka_worker.php emails

# Server 3
php kafka_worker.php emails
```

Kafka automatically distributes messages across workers in the same consumer group.

### Partitioning

Create topics with multiple partitions for better parallelism:

```bash
# Create topic with 10 partitions
kafka-topics.sh --create \
  --topic my-app-jobs-emails \
  --partitions 10 \
  --replication-factor 3 \
  --bootstrap-server localhost:9092
```

Then run 10 workers to process all partitions in parallel.

## Monitoring

### Consumer Lag

Monitor consumer lag to ensure workers are keeping up:

```bash
# Check consumer lag
kafka-consumer-groups.sh --bootstrap-server localhost:9092 \
  --group my-app-jobs-consumer \
  --describe
```

### Failed Jobs

Subscribe to the dead-letter queue to monitor failures:

```php
use Proto\Jobs\Drivers\KafkaDriver;

$config = [
    'driver' => 'kafka',
    'brokers' => 'localhost:9092',
    'group_id' => 'dead-letter-monitor',
];

$driver = new KafkaDriver($config);
$consumer = $driver->getConsumer();
$consumer->subscribe(['my-app-jobs-dead-letter']);

while (true) {
    $message = $consumer->consume(1000);
    if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR) {
        $failedJob = json_decode($message->payload, true);
        // Log or process failed job
        error_log("Failed job: " . json_encode($failedJob));
    }
}
```

## Limitations

### 1. Delayed Jobs

Kafka doesn't natively support delayed message delivery. When you dispatch a job with a delay:

```php
Jobs::dispatchLater(300, $job, $data); // 5 minute delay
```

The job is still sent to Kafka immediately with an `available_at` timestamp. Workers will skip jobs that aren't ready yet, but this isn't efficient for large delays.

**Solutions:**

- Use a separate timer service that dispatches jobs to Kafka when ready
- Use the database driver for delayed jobs and Kafka for immediate jobs
- Implement a delayed jobs topic with a dedicated consumer

### 2. Queue Statistics

Kafka doesn't provide easy access to queue depth without consuming messages. The `stats()` method has limited accuracy:

```php
$stats = Jobs::stats('emails');
// Only tracks in-memory state, not actual Kafka queue depth
```

**Solution:** Use Kafka monitoring tools like Kafka Manager, Confluent Control Center, or Prometheus with Kafka exporters.

### 3. Clearing Queues

The `clear()` method doesn't actually delete messages from Kafka:

```php
Jobs::clear('emails'); // Only clears local state
```

**Solution:** Use Kafka admin tools to delete topics or set retention policies.

## Best Practices

### 1. Consumer Groups

Use meaningful consumer group IDs:

```php
// Good - describes the purpose
'group_id' => 'order-processing-workers'

// Bad - generic name
'group_id' => 'workers'
```

### 2. Topic Naming

Use a clear topic prefix:

```php
'topic_prefix' => 'production-orders-jobs-'
```

### 3. Compression

Enable compression for better network efficiency:

```php
'compression' => 'snappy', // or 'lz4' for better compression ratio
```

### 4. Partition Strategy

Create topics with appropriate partition counts based on expected load:

- Low volume: 1-3 partitions
- Medium volume: 5-10 partitions
- High volume: 20+ partitions

### 5. Replication

Use replication factor ≥ 3 for production:

```bash
kafka-topics.sh --create \
  --topic my-app-jobs-emails \
  --partitions 10 \
  --replication-factor 3
```

### 6. Retention Policies

Set appropriate retention policies:

```bash
# Keep messages for 7 days
kafka-configs.sh --alter \
  --entity-type topics \
  --entity-name my-app-jobs-emails \
  --add-config retention.ms=604800000 \
  --bootstrap-server localhost:9092
```

## Comparison: Kafka vs Database Driver

| Feature | Kafka Driver | Database Driver |
|---------|-------------|-----------------|
| Throughput | Very High (100k+ msg/sec) | Medium (1k-10k msg/sec) |
| Latency | Low (<10ms) | Medium (10-100ms) |
| Scalability | Excellent (horizontal) | Good (vertical) |
| Persistence | Yes (configurable) | Yes (permanent) |
| Delayed Jobs | Limited | Excellent |
| Failed Job Tracking | Limited | Excellent |
| Setup Complexity | High | Low |
| Infrastructure | Kafka cluster required | Database only |
| Best For | High-volume, distributed | Simple, integrated |

## Troubleshooting

### Connection Issues

```php
// Enable librdkafka debug logging
Jobs::configure([
    'driver' => 'kafka',
    'brokers' => 'localhost:9092',
    'debug' => 'all', // Add this for debugging
]);
```

### Consumer Not Receiving Messages

1. Check consumer group offsets
2. Verify topic exists and has messages
3. Check partition assignment
4. Ensure `auto_offset_reset` is set correctly

### Performance Issues

1. Increase partition count
2. Add more workers
3. Tune batch sizes
4. Enable compression
5. Use faster serialization (consider msgpack instead of JSON)

## Migration from Database Driver

To migrate from database to Kafka:

1. Deploy Kafka cluster
2. Update configuration to use Kafka driver
3. Deploy new workers using Kafka
4. Gradually route new jobs to Kafka
5. Process remaining database jobs
6. Fully switch to Kafka

You can also run both drivers simultaneously using different queue names.
