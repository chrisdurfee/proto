# Proto Jobs System Documentation

The Proto Jobs system provides a robust background job processing solution with queue management, job scheduling, and retry capabilities.

## Features

- **Job Queuing**: Queue jobs for asynchronous processing
- **Job Scheduling**: Schedule jobs to run at specific times or intervals
- **Multiple Queues**: Support for multiple named queues with different priorities
- **Retry Logic**: Configurable retry attempts with exponential backoff
- **Database Storage**: Persistent job storage using database driver
- **Event System**: Job lifecycle events for monitoring and logging
- **Error Handling**: Comprehensive error handling and failure tracking

## Quick Start

### 1. Database Setup

First, run the database migration to create the required tables:

```php
use Proto\Jobs\Migrations\CreateJobsTables;

$migration = new CreateJobsTables();
$migration->up();
```

This creates two tables:
- `jobs`: Stores queued and processing jobs
- `failed_jobs`: Stores failed jobs for debugging

### 2. Creating a Job

Create a job class that implements the `JobInterface`:

```php
<?php
use Proto\Jobs\Job;

class SendWelcomeEmailJob extends Job
{
    protected string $queue = 'emails';
    protected int $maxRetries = 3;
    protected int $timeout = 30;

    public function handle(mixed $data): mixed
    {
        $email = $data['email'];
        $name = $data['name'];

        // Send the email
        $this->sendEmail($email, $name);

        return ['email_sent' => true, 'recipient' => $email];
    }

    public function failed(\Throwable $exception, mixed $data): void
    {
        // Handle job failure
        error_log("Failed to send welcome email to {$data['email']}: " . $exception->getMessage());
    }

    protected function sendEmail(string $email, string $name): void
    {
        // Your email sending logic here
    }
}
```

### 3. Queuing Jobs

```php
use Proto\Jobs\JobQueue;
use Proto\Jobs\Drivers\DatabaseDriver;

// Initialize the queue
$driver = new DatabaseDriver();
$queue = new JobQueue([], $driver);

// Queue a job
$job = new SendWelcomeEmailJob();
$data = [
    'email' => 'user@example.com',
    'name' => 'John Doe'
];

$queue->push($job, $data);
```

### 4. Processing Jobs

```php
// Process jobs continuously
$queue->work('emails'); // Process emails queue

// Process a specific number of jobs
$queue->work('emails', 10); // Process up to 10 jobs

// Process all queues
$queue->work(); // Defaults to 'default' queue
```

### 5. Scheduling Jobs

```php
use Proto\Jobs\Scheduler;

$scheduler = new Scheduler($queue);

// Schedule a job to run in 5 minutes
$scheduler->in($job, 300, $data); // 300 seconds = 5 minutes

// Schedule a job to run at a specific time
$scheduler->at($job, '2024-01-01 12:00:00', $data);

// Schedule a recurring job every hour
$scheduler->every($job, 3600, $data); // 3600 seconds = 1 hour

// Schedule daily at specific time
$scheduler->daily($job, $data, '02:00'); // 2 AM daily

// Schedule weekly
$scheduler->weekly($job, $data, 'monday', '09:00'); // Monday 9 AM
```

## Job Configuration

### Job Properties

Jobs can be configured by setting protected properties:

```php
class MyJob extends Job
{
    protected string $queue = 'high-priority';    // Queue name
    protected int $maxRetries = 5;                // Maximum retry attempts
    protected int $retryDelay = 60;               // Seconds between retries
    protected int $timeout = 300;                 // Job timeout in seconds
}
```

### Runtime Configuration

You can also configure jobs at runtime:

```php
$job = new MyJob();
$job->onQueue('urgent')           // Change queue
    ->retries(3)                  // Set max retries
    ->retryAfter(30)              // Set retry delay
    ->timeout(120);               // Set timeout
```

## Queue Management

### Multiple Queues

Use different queues for different types of jobs:

```php
// High priority queue for urgent tasks
$job->onQueue('high-priority');
$queue->push($job, $data);

// Background queue for heavy processing
$job->onQueue('background');
$queue->push($job, $data);

// Email queue for notifications
$job->onQueue('emails');
$queue->push($job, $data);
```

### Processing Specific Queues

```php
// Process high priority jobs first
$queue->work('high-priority', 5);

// Then process normal jobs
$queue->work('default', 10);

// Finally process background jobs
$queue->work('background', 3);
```

### Delayed Jobs

```php
// Queue a job to run later
$queue->later(3600, $job, $data); // Run in 1 hour
```

## Worker Scripts

### Basic Worker

Use the provided worker script to process jobs continuously:

```bash
# Process default queue
php job_worker.php

# Process specific queue
php job_worker.php emails

# Process with limits
php job_worker.php default 100    # Process max 100 jobs
php job_worker.php default 0 5    # Check every 5 seconds
```

### Custom Worker

Create your own worker script:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Proto\Jobs\JobQueue;
use Proto\Jobs\Drivers\DatabaseDriver;

$driver = new DatabaseDriver();
$queue = new JobQueue([], $driver);

while (true) {
    $queue->work('default', 1);
    sleep(3); // Wait 3 seconds between jobs
}
```

## Scheduler Setup

### Cron Setup

Add this to your crontab to run the scheduler every minute:

```bash
* * * * * php /path/to/your/app/scheduler.php >> /var/log/scheduler.log 2>&1
```

### Manual Scheduler Run

```bash
# Run scheduler manually
php scheduler.php

# Run with verbose output
php scheduler.php --verbose
```

## Monitoring and Debugging

### Queue Statistics

```php
$stats = $driver->getStats();
echo "Pending: " . $stats['pending'] . "\n";
echo "Processing: " . $stats['processing'] . "\n";
echo "Completed: " . $stats['completed'] . "\n";
echo "Failed: " . $stats['failed'] . "\n";
```

### Queue-specific Statistics

```php
$stats = $driver->getStats('emails');
// Returns stats for 'emails' queue only
```

### Failed Jobs

Access failed jobs through the FailedJobModel:

```php
use Proto\Jobs\Models\FailedJobModel;

$failedJobs = FailedJobModel::all();
foreach ($failedJobs as $failedJob) {
    echo "Job: " . $failedJob->job_class . "\n";
    echo "Failed at: " . $failedJob->failed_at . "\n";
    echo "Error: " . $failedJob->exception . "\n";
}
```

### Event Listening

Listen to job events:

```php
<?php
use Proto\Events\Events;

// Use static methods - no need to instantiate
Events::on('job.processing', function($jobEvent) {
    echo "Processing job: " . $jobEvent->get('job_class') . "\n";
});

Events::on('job.processed', function($jobEvent) {
    echo "Completed job: " . $jobEvent->get('job_class') . "\n";
});

Events::on('job.failed', function($jobEvent) {
    echo "Failed job: " . $jobEvent->get('job_class') . "\n";
});
```

## Best Practices

### 1. Job Design

- Keep jobs small and focused on a single task
- Make jobs idempotent (safe to run multiple times)
- Use appropriate timeouts for your jobs
- Handle exceptions gracefully

### 2. Queue Organization

- Use separate queues for different types of work
- Process high-priority queues first
- Use background queues for heavy processing

### 3. Error Handling

- Implement meaningful failure handlers
- Log errors appropriately
- Consider retry strategies carefully

### 4. Performance

- Monitor queue lengths
- Scale workers based on load
- Use appropriate database indexes
- Clean up old completed jobs regularly

### 5. Deployment

- Use process managers like Supervisor for workers
- Set up proper logging
- Monitor worker health
- Have alerting for failed jobs

## Example Jobs

The system includes several example jobs:

- **SendEmailJob**: Email sending example
- **ProcessImageJob**: Image processing with multiple operations
- **DataCleanupJob**: System maintenance and cleanup

See the `src/Jobs/Examples/` directory for complete implementations.

## Configuration

### Database Configuration

The system uses your existing Proto database configuration. Ensure your database connection is properly configured in your Proto application.

### Queue Configuration

Default configuration options:

```php
$config = [
    'default_queue' => 'default',
    'retry_delay' => 60,           // Default retry delay in seconds
    'max_retries' => 3,            // Default max retries
    'timeout' => 60,               // Default job timeout
    'failed_job_retention' => 168  // Keep failed jobs for 168 hours (1 week)
];

$queue = new JobQueue($config, $driver);
```

## Troubleshooting

### Common Issues

1. **Jobs not processing**: Check that workers are running
2. **Database errors**: Ensure migrations have been run
3. **Memory issues**: Increase PHP memory limit for heavy jobs
4. **Timeout issues**: Adjust job timeout settings

### Debugging

Enable verbose logging by setting error reporting:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

Check the failed_jobs table for detailed error information.
