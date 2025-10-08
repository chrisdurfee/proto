# Proto Jobs System

A robust, scalable background job processing system for PHP applications with support for multiple queue drivers and flexible scheduling.

## 🚀 Features

- ✅ **Multiple Drivers**: Database and Kafka support out of the box
- ✅ **Job Scheduling**: Run jobs at specific times or on recurring schedules
- ✅ **Retry Logic**: Configurable retry attempts with exponential backoff
- ✅ **Multiple Queues**: Organize jobs by priority and type
- ✅ **Event System**: Monitor job lifecycle with events
- ✅ **Failed Job Tracking**: Track and retry failed jobs
- ✅ **Graceful Shutdown**: Handle signals properly
- ✅ **Simple API**: Easy-to-use facade for common operations

## 📦 Installation

The Jobs system is included in the Proto framework. Just run the database migration:

```php
use Proto\Jobs\Migrations\CreateJobsTables;

$migration = new CreateJobsTables();
$migration->up();
```

## 🏃 Quick Start

### 1. Configure

```php
use Proto\Jobs\Jobs;

Jobs::configure([
    'driver' => 'database',
    'connection' => 'default',
]);
```

### 2. Create a Job

```php
use Proto\Jobs\Job;

class SendEmailJob extends Job
{
    protected string $queue = 'emails';

    public function handle(mixed $data): mixed
    {
        mail($data['to'], $data['subject'], $data['body']);
        return ['sent' => true];
    }
}
```

### 3. Dispatch

```php
Jobs::dispatch(SendEmailJob::class, [
    'to' => 'user@example.com',
    'subject' => 'Hello!',
    'body' => 'This is a test.'
]);
```

### 4. Process

```bash
php worker.php emails
```

## 📚 Documentation

- **[Quick Start Guide](QUICK_START_GUIDE.md)** - Get up and running in 5 minutes
- **[Complete Documentation](JOBS_DOCUMENTATION.md)** - Full API reference and usage
- **[Kafka Driver Guide](KAFKA_DRIVER_DOCUMENTATION.md)** - High-volume distributed processing
- **[Review & Improvements](REVIEW_AND_IMPROVEMENTS.md)** - Recent changes and fixes

## 💡 Usage Examples

### Basic Dispatch

```php
// Simple dispatch
Jobs::dispatch(SendEmailJob::class, $data);

// Delayed dispatch (5 minutes)
Jobs::dispatchLater(300, SendEmailJob::class, $data);

// Specific queue
Jobs::dispatch(SendEmailJob::class, $data, 'high-priority');
```

### Fluent Configuration

```php
$job = new SendEmailJob();
$job->onQueue('urgent')
    ->retries(5)
    ->retryAfter(60)
    ->timeout(120);

Jobs::dispatch($job, $data);
```

### Scheduling

```php
// Run at specific time
Jobs::scheduleAt(SendEmailJob::class, '2024-12-31 23:59:59', $data);

// Run every hour
Jobs::scheduleEvery(SendEmailJob::class, 3600, $data);

// Run daily at 2 AM
Jobs::scheduleDaily(SendEmailJob::class, $data, null, '02:00');
```

### Monitoring

```php
// Queue statistics
$stats = Jobs::stats('emails');
echo "Pending: {$stats['pending']}\n";
echo "Failed: {$stats['failed']}\n";

// Failed jobs
$failedJobs = Jobs::failedJobs();
foreach ($failedJobs as $job) {
    echo "Failed: {$job->job_name} - {$job->error}\n";
}

// Retry failed job
Jobs::retry($jobId);
```

### Event Listening

```php
Jobs::listen('job.processed', function($event) {
    $time = $event->get('execution_time');
    echo "Job completed in {$time}s\n";
});

Jobs::listen('job.failed', function($event) {
    $error = $event->get('exception')->getMessage();
    error_log("Job failed: {$error}");
});
```

## 🔧 Drivers

### Database Driver (Default)

Best for most applications. Simple setup, integrated with your existing database.

```php
Jobs::configure(['driver' => 'database']);
```

**Pros:**
- Simple setup
- Integrated with existing infrastructure
- Excellent delayed job support
- Complete failed job tracking

**Cons:**
- Limited by database performance
- Single point of failure

### Kafka Driver

Best for high-volume, distributed systems requiring horizontal scaling.

```php
Jobs::configure([
    'driver' => 'kafka',
    'brokers' => 'localhost:9092',
    'group_id' => 'my-app-workers',
]);
```

**Pros:**
- Very high throughput (100k+ jobs/sec)
- Horizontal scaling
- Fault tolerant
- Low latency

**Cons:**
- More complex setup
- Requires Kafka cluster
- Limited delayed job support

See [Kafka Driver Documentation](KAFKA_DRIVER_DOCUMENTATION.md) for details.

## 🏭 Production Deployment

### Using Supervisor

```ini
[program:proto-worker-emails]
command=php /var/www/app/worker.php emails
directory=/var/www/app
user=www-data
numprocs=3
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/proto-worker-emails.log
```

### Using Systemd

```ini
[Unit]
Description=Proto Worker: %i
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/app
ExecStart=/usr/bin/php /var/www/app/worker.php %i
Restart=always
RestartSec=3
```

Enable and start:

```bash
systemctl enable proto-worker@emails
systemctl start proto-worker@emails
```

## 📊 Performance

| Driver | Throughput | Latency | Best For |
|--------|-----------|---------|----------|
| Database | 1k-10k/sec | 10-100ms | Most apps |
| Kafka | 100k+/sec | <10ms | High volume |

## 🎯 Best Practices

1. **Keep jobs small and focused** - One job, one responsibility
2. **Make jobs idempotent** - Safe to run multiple times
3. **Use appropriate timeouts** - Match timeout to job complexity
4. **Handle failures gracefully** - Implement proper error handling
5. **Monitor queue depth** - Set up alerts for growing queues
6. **Clean up old jobs** - Run periodic cleanup tasks

## 🐛 Troubleshooting

### Jobs not processing?

1. Check workers are running: `ps aux | grep worker.php`
2. Check logs: `tail -f logs/worker.log`
3. Verify configuration
4. Check queue stats: `Jobs::stats()`

### High memory usage?

1. Limit jobs processed per worker
2. Restart workers periodically
3. Use Supervisor's memory limits

### Failed jobs piling up?

1. Review error logs
2. Fix underlying issues
3. Retry failed jobs
4. Clear old failures

## 🔍 Examples

Check the `Examples/` directory for:

- **SendEmailJob.php** - Email sending example
- **ProcessImageJob.php** - Image processing example
- **DataCleanupJob.php** - Cleanup job example
- **ImprovedApiExample.php** - Complete API usage example

## 📝 API Reference

### Jobs Facade

```php
// Configuration
Jobs::configure(array $config): void

// Dispatching
Jobs::dispatch($job, $data, $queue, $delay): bool
Jobs::dispatchLater($delay, $job, $data, $queue): bool

// Scheduling
Jobs::scheduleAt($job, $time, $data, $queue): ScheduledJob
Jobs::scheduleIn($job, $delay, $data, $queue): ScheduledJob
Jobs::scheduleEvery($job, $interval, $data, $queue): ScheduledJob
Jobs::scheduleDaily($job, $data, $queue, $time): ScheduledJob

// Processing
Jobs::work($queue, $maxJobs): void
Jobs::stop(): void

// Monitoring
Jobs::stats($queue): array
Jobs::failedJobs($limit, $offset): array
Jobs::retry($jobId): bool
Jobs::clear($queue): bool

// Events
Jobs::listen($event, $callback): void
```

### Job Class

```php
class YourJob extends Job
{
    protected string $queue = 'default';
    protected int $maxRetries = 3;
    protected int $retryDelay = 60;
    protected int $timeout = 300;

    public function handle(mixed $data): mixed
    {
        // Your job logic
    }

    public function failed(\Throwable $e, mixed $data): void
    {
        // Handle failure
    }

    public function shouldRetry(int $attempts, \Throwable $e): bool
    {
        // Custom retry logic
    }
}
```

## 🤝 Contributing

Contributions welcome! Please:

1. Write tests for new features
2. Follow PSR-12 coding standards
3. Update documentation
4. Add examples for new features

## 📄 License

Part of the Proto framework. See main framework license.

## 🆘 Support

- **Documentation**: See `docs/` directory
- **Examples**: See `Examples/` directory
- **Issues**: Open an issue on GitHub

## 🎉 Recent Improvements

### v2.0 (October 2025)

- ✅ Added Kafka driver for high-volume processing
- ✅ New Jobs facade for simplified API
- ✅ Fluent job configuration methods
- ✅ Improved error handling
- ✅ Better transaction management
- ✅ Fixed cleanup method return values
- ✅ Comprehensive documentation updates
- ✅ Worker script template
- ✅ Production deployment examples

See [REVIEW_AND_IMPROVEMENTS.md](REVIEW_AND_IMPROVEMENTS.md) for complete details.

---

**Made with ❤️ by the Proto team**
