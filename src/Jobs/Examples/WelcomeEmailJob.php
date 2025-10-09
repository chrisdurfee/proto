<?php declare(strict_types=1);

/**
 * Example: Using the Jobs System with the new Jobs Facade
 *
 * This example demonstrates the improved, simplified API for dispatching
 * and processing background jobs in the Proto framework.
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Proto\Jobs\Jobs;

// ============================================================================
// 1. CONFIGURATION
// ============================================================================

// Configure once at application bootstrap
Jobs::configure([
    'driver' => 'database',
    'connection' => 'default',
    'max_workers' => 3,
    'timeout' => 60,
    'sleep' => 3,
]);

// Or use Kafka for high-volume scenarios
// Jobs::configure([
//     'driver' => 'kafka',
//     'brokers' => 'localhost:9092',
//     'group_id' => 'my-app-workers',
// ]);

// ============================================================================
// 2. CREATING JOBS
// ============================================================================

class WelcomeEmailJob extends \Proto\Jobs\Job
{
    protected string $queue = 'emails';
    protected int $maxRetries = 3;
    protected int $timeout = 30;

    public function handle(mixed $data): mixed
    {
        $email = $data['email'];
        $name = $data['name'];

        echo "Sending welcome email to {$email}...\n";

        // Simulate sending email
        sleep(1);

        return ['sent' => true, 'timestamp' => date('Y-m-d H:i:s')];
    }

    public function failed(\Throwable $exception, mixed $data): void
    {
        error_log("Failed to send welcome email to {$data['email']}: " . $exception->getMessage());
    }
}

class ProcessOrderJob extends \Proto\Jobs\Job
{
    protected string $queue = 'orders';

    public function handle(mixed $data): mixed
    {
        $orderId = $data['order_id'];
        echo "Processing order #{$orderId}...\n";

        // Simulate order processing
        sleep(2);

        return ['processed' => true, 'order_id' => $orderId];
    }
}

// ============================================================================
// 3. DISPATCHING JOBS - SIMPLE
// ============================================================================

echo "=== Simple Job Dispatch ===\n";

// Dispatch a job immediately
Jobs::dispatch(WelcomeEmailJob::class, [
    'email' => 'user@example.com',
    'name' => 'John Doe'
]);

echo "✓ Welcome email job dispatched\n";

// Dispatch to a specific queue
Jobs::dispatch(ProcessOrderJob::class, [
    'order_id' => 12345
], 'orders');

echo "✓ Order processing job dispatched\n";

// ============================================================================
// 4. DISPATCHING JOBS - WITH DELAY
// ============================================================================

echo "\n=== Delayed Job Dispatch ===\n";

// Dispatch a job to run in 5 minutes (300 seconds)
Jobs::dispatchLater(300, WelcomeEmailJob::class, [
    'email' => 'delayed@example.com',
    'name' => 'Jane Smith'
]);

echo "✓ Delayed welcome email job dispatched (5 minutes)\n";

// ============================================================================
// 5. FLUENT JOB CONFIGURATION
// ============================================================================

echo "\n=== Fluent Job Configuration ===\n";

$job = new WelcomeEmailJob();
$job->onQueue('urgent')
    ->retries(5)              // New method!
    ->retryAfter(60)          // New method!
    ->timeout(120);           // New method!

Jobs::dispatch($job, [
    'email' => 'vip@example.com',
    'name' => 'VIP Customer'
]);

echo "✓ VIP email job dispatched with custom configuration\n";

// ============================================================================
// 6. SCHEDULING JOBS
// ============================================================================

echo "\n=== Scheduled Jobs ===\n";

// Schedule for specific time
Jobs::scheduleAt(WelcomeEmailJob::class, '+1 hour', [
    'email' => 'scheduled@example.com',
    'name' => 'Scheduled User'
]);

echo "✓ Job scheduled for 1 hour from now\n";

// Schedule recurring job - every 5 minutes
Jobs::scheduleEvery(ProcessOrderJob::class, 300, [
    'order_id' => 'recurring'
]);

echo "✓ Recurring job scheduled (every 5 minutes)\n";

// Schedule daily at 2 AM
Jobs::scheduleDaily(WelcomeEmailJob::class, [
    'email' => 'daily@example.com',
    'name' => 'Daily User'
], null, '02:00');

echo "✓ Daily job scheduled (2 AM)\n";

// ============================================================================
// 7. MONITORING WITH EVENTS
// ============================================================================

echo "\n=== Event Monitoring ===\n";

// Listen for job processing
Jobs::listen('job.processing', function($event) {
    $jobName = $event->get('job_name');
    echo "→ Processing: {$jobName}\n";
});

// Listen for job completion
Jobs::listen('job.processed', function($event) {
    $jobName = $event->get('job_name');
    $executionTime = number_format($event->get('execution_time'), 2);
    echo "✓ Completed: {$jobName} in {$executionTime}s\n";
});

// Listen for job failures
Jobs::listen('job.failed', function($event) {
    $jobName = $event->get('job_name');
    $exception = $event->get('exception');
    echo "✗ Failed: {$jobName} - {$exception->getMessage()}\n";
});

echo "✓ Event listeners registered\n";

// ============================================================================
// 8. QUEUE STATISTICS
// ============================================================================

echo "\n=== Queue Statistics ===\n";

$stats = Jobs::stats();
echo "Pending: {$stats['pending']}\n";
echo "Processing: {$stats['processing']}\n";
echo "Completed: {$stats['completed']}\n";
echo "Failed: {$stats['failed']}\n";
echo "Total: {$stats['total']}\n";

// Get stats for specific queue
$emailStats = Jobs::stats('emails');
echo "\nEmail Queue:\n";
echo "Pending: {$emailStats['pending']}\n";

// ============================================================================
// 9. PROCESSING JOBS (WORKER)
// ============================================================================

echo "\n=== Processing Jobs ===\n";

// Process jobs from the 'emails' queue
// In production, this would be in a separate worker.php file
echo "Processing jobs from 'emails' queue...\n";

// Process 3 jobs then stop
Jobs::work('emails', 3);

echo "\n✓ Jobs processed\n";

// ============================================================================
// 10. FAILED JOBS HANDLING
// ============================================================================

echo "\n=== Failed Jobs ===\n";

$failedJobs = Jobs::failedJobs(5);
echo "Found " . count($failedJobs) . " failed jobs\n";

foreach ($failedJobs as $job) {
    echo "- Job: {$job->job_name}\n";
    echo "  Error: {$job->error}\n";
    echo "  Failed at: {$job->failed_at}\n";

    // Retry failed job
    // Jobs::retry($job->job_id);
}

// ============================================================================
// 11. COMPARISON: OLD VS NEW API
// ============================================================================

echo "\n=== API Comparison ===\n";

// OLD WAY (still works, but verbose)
echo "OLD API:\n";
$driver = new \Proto\Jobs\Drivers\DatabaseDriver();
$queue = new \Proto\Jobs\JobQueue([], $driver);
$queue->push(new WelcomeEmailJob(), ['email' => 'old@example.com', 'name' => 'Old Way']);
echo "✓ Job dispatched using old API\n";

// NEW WAY (simplified)
echo "\nNEW API:\n";
Jobs::dispatch(WelcomeEmailJob::class, ['email' => 'new@example.com', 'name' => 'New Way']);
echo "✓ Job dispatched using new API\n";

// ============================================================================
// COMPLETE!
// ============================================================================

echo "\n" . str_repeat("=", 60) . "\n";
echo "Example completed successfully!\n";
echo "\nNext steps:\n";
echo "1. Run 'php worker.php' to process jobs\n";
echo "2. Check queue stats with Jobs::stats()\n";
echo "3. Monitor failed jobs with Jobs::failedJobs()\n";
echo "4. Read QUICK_START_GUIDE.md for more examples\n";
echo str_repeat("=", 60) . "\n";
