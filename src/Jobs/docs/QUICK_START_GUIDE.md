# Jobs System Quick Start Guide

Get started with the Proto Jobs system in 5 minutes!

## Installation

### 1. Database Setup (for Database Driver)

Run the migration to create job tables:

```php
use Proto\Jobs\Migrations\CreateJobsTables;

$migration = new CreateJobsTables();
$migration->up();
```

### 2. Configuration

Configure the jobs system in your application bootstrap:

```php
use Proto\Jobs\Jobs;

// Using database driver (default)
Jobs::configure([
    'driver' => 'database',
    'connection' => 'default',
]);

// Or using Kafka driver
Jobs::configure([
    'driver' => 'kafka',
    'brokers' => 'localhost:9092',
    'group_id' => 'my-app-workers',
]);
```

## Creating Your First Job

Create a job class:

```php
<?php
namespace App\Jobs;

use Proto\Jobs\Job;

class SendWelcomeEmail extends Job
{
    protected string $queue = 'emails';
    protected int $maxRetries = 3;
    protected int $timeout = 30;

    public function handle(mixed $data): mixed
    {
        // Your job logic here
        $email = $data['email'];
        $name = $data['name'];

        // Send email...
        mail($email, 'Welcome!', "Hello {$name}!");

        return ['sent' => true];
    }

    public function failed(\Throwable $exception, mixed $data): void
    {
        // Handle failure
        error_log("Failed to send email: " . $exception->getMessage());
    }
}
```

## Dispatching Jobs

### Immediate Dispatch

```php
use Proto\Jobs\Jobs;
use App\Jobs\SendWelcomeEmail;

// Simple dispatch
Jobs::dispatch(SendWelcomeEmail::class, [
    'email' => 'user@example.com',
    'name' => 'John Doe'
]);

// Dispatch to specific queue
Jobs::dispatch(SendWelcomeEmail::class, $data, 'high-priority');
```

### Delayed Dispatch

```php
// Dispatch in 5 minutes (300 seconds)
Jobs::dispatchLater(300, SendWelcomeEmail::class, $data);
```

### Fluent Job Configuration

```php
$job = new SendWelcomeEmail();
$job->onQueue('urgent')
    ->retries(5)
    ->retryAfter(60)
    ->timeout(120);

Jobs::dispatch($job, $data);
```

## Scheduling Jobs

### Schedule at Specific Time

```php
use Proto\Jobs\Jobs;

// Schedule for specific date/time
Jobs::scheduleAt(SendWelcomeEmail::class, '2024-12-31 23:59:59', $data);

// Schedule in 1 hour
Jobs::scheduleAt(SendWelcomeEmail::class, '+1 hour', $data);
```

### Recurring Jobs

```php
// Run every 5 minutes
Jobs::scheduleEvery(SendWelcomeEmail::class, 300, $data);

// Run daily at 2 AM
Jobs::scheduleDaily(SendWelcomeEmail::class, $data, null, '02:00');
```

## Processing Jobs (Workers)

### Create a Worker Script

Create `worker.php`:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Proto\Jobs\Jobs;

// Configure
Jobs::configure([
    'driver' => 'database',
    'connection' => 'default',
]);

// Handle graceful shutdown
pcntl_signal(SIGTERM, fn() => Jobs::stop());
pcntl_signal(SIGINT, fn() => Jobs::stop());

// Get queue name from command line
$queue = $argv[1] ?? 'default';

echo "Worker started for queue: {$queue}\n";

// Process jobs
Jobs::work($queue);
```

### Run the Worker

```bash
# Process default queue
php worker.php

# Process specific queue
php worker.php emails

# Run in background
nohup php worker.php emails > logs/worker-emails.log 2>&1 &
```

## Monitoring

### Check Queue Statistics

```php
use Proto\Jobs\Jobs;

$stats = Jobs::stats('emails');
echo "Pending: {$stats['pending']}\n";
echo "Processing: {$stats['processing']}\n";
echo "Completed: {$stats['completed']}\n";
echo "Failed: {$stats['failed']}\n";
```

### View Failed Jobs

```php
$failedJobs = Jobs::failedJobs(10);
foreach ($failedJobs as $job) {
    echo "Job: {$job->job_name}\n";
    echo "Error: {$job->error}\n";
    echo "Failed at: {$job->failed_at}\n";
}
```

### Retry Failed Jobs

```php
// Retry a specific job
Jobs::retry($jobId);
```

## Event Monitoring

Listen to job events:

```php
use Proto\Jobs\Jobs;

// Listen for job processing
Jobs::listen('job.processing', function($event) {
    $jobClass = $event->get('job_class');
    echo "Processing: {$jobClass}\n";
});

// Listen for job completion
Jobs::listen('job.processed', function($event) {
    $jobClass = $event->get('job_class');
    $executionTime = $event->get('execution_time');
    echo "Completed: {$jobClass} in {$executionTime}s\n";
});

// Listen for job failures
Jobs::listen('job.failed', function($event) {
    $jobClass = $event->get('job_class');
    $exception = $event->get('exception');
    echo "Failed: {$jobClass} - {$exception->getMessage()}\n";
});
```

## Advanced Usage

### Multiple Queues

```php
// High priority queue
Jobs::dispatch(UrgentJob::class, $data, 'urgent');

// Normal priority
Jobs::dispatch(NormalJob::class, $data, 'default');

// Low priority background jobs
Jobs::dispatch(CleanupJob::class, $data, 'background');
```

### Custom Driver Configuration

```php
use Proto\Jobs\JobQueue;
use Proto\Jobs\Drivers\DatabaseDriver;

$driver = new DatabaseDriver([
    'connection' => 'jobs_db',
    'table' => 'custom_jobs',
]);

$queue = new JobQueue([], $driver);
$queue->push($job, $data);
```

### Using Raw Queue Instance

```php
// Get the underlying queue instance for advanced usage
$queue = Jobs::queue();

// Access driver directly
$driver = $queue->getDriver();
$stats = $driver->getStats('emails');
```

## Best Practices

### 1. Keep Jobs Small

```php
// Good - focused job
class SendEmailJob extends Job {
    public function handle(mixed $data): mixed {
        $this->sendEmail($data['email'], $data['message']);
    }
}

// Bad - too many responsibilities
class ProcessOrderJob extends Job {
    public function handle(mixed $data): mixed {
        $this->validateOrder($data);
        $this->chargePayment($data);
        $this->sendEmail($data);
        $this->updateInventory($data);
        $this->notifyWarehouse($data);
    }
}
```

### 2. Make Jobs Idempotent

```php
class ProcessPaymentJob extends Job {
    public function handle(mixed $data): mixed {
        $orderId = $data['order_id'];

        // Check if already processed
        if ($this->isProcessed($orderId)) {
            return ['status' => 'already_processed'];
        }

        // Process payment
        $result = $this->processPayment($orderId);

        // Mark as processed
        $this->markProcessed($orderId);

        return $result;
    }
}
```

### 3. Use Appropriate Timeouts

```php
class QuickEmailJob extends Job {
    protected int $timeout = 30; // 30 seconds for quick tasks
}

class VideoProcessingJob extends Job {
    protected int $timeout = 3600; // 1 hour for heavy processing
}
```

### 4. Handle Failures Gracefully

```php
class ImportDataJob extends Job {
    public function handle(mixed $data): mixed {
        try {
            $this->importData($data['file']);
        } catch (ValidationException $e) {
            // Don't retry validation errors
            throw new \RuntimeException('Validation failed: ' . $e->getMessage());
        } catch (NetworkException $e) {
            // Retry network errors
            throw $e;
        }
    }

    public function shouldRetry(int $attempts, \Throwable $exception): bool {
        // Don't retry validation errors
        if ($exception->getMessage() === 'Validation failed') {
            return false;
        }
        return parent::shouldRetry($attempts, $exception);
    }
}
```

## Production Deployment

### Using Supervisor (Recommended)

Create `/etc/supervisor/conf.d/proto-workers.conf`:

```ini
[program:proto-worker-default]
command=php /path/to/your/app/worker.php default
directory=/path/to/your/app
user=www-data
numprocs=3
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor/proto-worker-default.log

[program:proto-worker-emails]
command=php /path/to/your/app/worker.php emails
directory=/path/to/your/app
user=www-data
numprocs=2
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor/proto-worker-emails.log
```

Reload supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start proto-worker-default:*
sudo supervisorctl start proto-worker-emails:*
```

### Using Systemd

Create `/etc/systemd/system/proto-worker@.service`:

```ini
[Unit]
Description=Proto Worker: %i
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/your/app
ExecStart=/usr/bin/php /path/to/your/app/worker.php %i
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl enable proto-worker@default
sudo systemctl enable proto-worker@emails
sudo systemctl start proto-worker@default
sudo systemctl start proto-worker@emails
```

## Next Steps

- Read the [full documentation](JOBS_DOCUMENTATION.md)
- Learn about [Kafka driver](KAFKA_DRIVER_DOCUMENTATION.md) for high-volume scenarios
- Check out [example jobs](Examples/) for more patterns
- Set up monitoring and alerting for your workers

## Common Issues

### Jobs Not Processing

1. Check workers are running: `ps aux | grep worker.php`
2. Check for errors: `tail -f logs/worker.log`
3. Verify database connection
4. Check job status: `Jobs::stats()`

### High Memory Usage

1. Limit jobs processed: `Jobs::work('default', 100)`
2. Restart workers periodically
3. Use Supervisor's `stopwaitsecs` and `stopasgroup`

### Failed Jobs Piling Up

1. Review error logs
2. Fix underlying issues
3. Retry failed jobs: `Jobs::retry($jobId)`
4. Clear old failures: `$driver->cleanupFailedJobs(30)`

Need help? Check the documentation or open an issue!
