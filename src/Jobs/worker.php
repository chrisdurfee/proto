<?php declare(strict_types=1);

/**
 * Proto Jobs - Worker Script Template
 *
 * This is a template worker script for processing jobs from the Proto Jobs system.
 * Copy this file and customize it for your application.
 *
 * Usage:
 *   php worker.php [queue-name] [max-jobs]
 *
 * Examples:
 *   php worker.php                  # Process default queue indefinitely
 *   php worker.php emails           # Process emails queue indefinitely
 *   php worker.php emails 100       # Process 100 jobs then stop
 *
 * Run in background:
 *   nohup php worker.php emails > logs/worker-emails.log 2>&1 &
 *
 * For production, use a process manager like Supervisor or Systemd.
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Proto\Jobs\Jobs;

// ============================================================================
// CONFIGURATION
// ============================================================================

// Configure the jobs system
Jobs::configure([
    // Driver: 'database' or 'kafka'
    'driver' => getenv('JOBS_DRIVER') ?: 'database',

    // Database driver settings
    'connection' => getenv('DB_CONNECTION') ?: 'default',
    'table' => 'jobs',
    'failed_table' => 'failed_jobs',

    // Kafka driver settings (if using Kafka)
    'brokers' => getenv('KAFKA_BROKERS') ?: 'localhost:9092',
    'group_id' => getenv('KAFKA_GROUP_ID') ?: 'proto-jobs-workers',
    'topic_prefix' => getenv('KAFKA_TOPIC_PREFIX') ?: 'proto-jobs-',

    // Worker settings
    'max_workers' => 1,
    'memory_limit' => 128,  // MB
    'timeout' => 60,        // seconds
    'sleep' => 3,           // seconds between checks
]);

// ============================================================================
// COMMAND LINE ARGUMENTS
// ============================================================================

$queueName = $argv[1] ?? 'default';
$maxJobs = isset($argv[2]) ? (int)$argv[2] : 0; // 0 = unlimited

// ============================================================================
// LOGGING SETUP
// ============================================================================

function workerLog(string $message, string $level = 'INFO'): void
{
    $timestamp = date('Y-m-d H:i:s');
    $pid = getmypid();
    echo "[{$timestamp}] [{$level}] [PID:{$pid}] {$message}\n";
}

// ============================================================================
// SIGNAL HANDLERS (Graceful Shutdown)
// ============================================================================

$shutdownRequested = false;

if (function_exists('pcntl_signal')) {
    // Handle SIGTERM (kill)
    /**
     * @suppresswarnings PHP0415
     */
    pcntl_signal(SIGTERM, function() use (&$shutdownRequested) {
        workerLog('Received SIGTERM signal, shutting down gracefully...', 'INFO');
        $shutdownRequested = true;
        Jobs::stop();
    });

    // Handle SIGINT (Ctrl+C)
    pcntl_signal(SIGINT, function() use (&$shutdownRequested) {
        workerLog('Received SIGINT signal, shutting down gracefully...', 'INFO');
        $shutdownRequested = true;
        Jobs::stop();
    });

    // Handle SIGUSR1 (custom: pause worker)
    pcntl_signal(SIGUSR1, function() {
        workerLog('Received SIGUSR1 signal, pausing worker...', 'INFO');
        Jobs::stop();
    });
} else {
    workerLog('PCNTL extension not available, graceful shutdown disabled', 'WARNING');
}

// ============================================================================
// EVENT LISTENERS (Monitoring & Logging)
// ============================================================================

// Log when jobs start processing
Jobs::listen('job.processing', function($event) {
    $jobName = $event->get('job_name');
    $jobId = $event->get('id');
    workerLog("Processing job: {$jobName} (ID: {$jobId})");
});

// Log when jobs complete
Jobs::listen('job.processed', function($event) {
    $jobName = $event->get('job_name');
    $jobId = $event->get('id');
    $executionTime = number_format($event->get('execution_time', 0), 2);
    workerLog("Completed job: {$jobName} (ID: {$jobId}) in {$executionTime}s", 'SUCCESS');
});

// Log when jobs fail
Jobs::listen('job.failed', function($event) {
    $jobName = $event->get('job_name');
    $jobId = $event->get('id');
    $exception = $event->get('exception');
    $attempts = $event->get('attempts', 0);
    $message = $exception ? $exception->getMessage() : 'Unknown error';
    workerLog("Failed job: {$jobName} (ID: {$jobId}) after {$attempts} attempts - {$message}", 'ERROR');
});

// Log worker lifecycle events
Jobs::listen('worker.starting', function($event) use ($queueName, $maxJobs) {
    $queue = $event->get('queue', $queueName);
    $jobLimit = $maxJobs > 0 ? " (max {$maxJobs} jobs)" : " (unlimited)";
    workerLog("Worker starting for queue: {$queue}{$jobLimit}");
});

Jobs::listen('worker.stopped', function($event) {
    $queue = $event->get('queue');
    $processed = $event->get('processed', 0);
    workerLog("Worker stopped for queue: {$queue} (processed {$processed} jobs)");
});

Jobs::listen('worker.memory_exceeded', function($event) {
    $memory = number_format($event->get('memory', 0) / 1024 / 1024, 2);
    workerLog("Memory limit exceeded: {$memory}MB, stopping worker", 'WARNING');
});

// ============================================================================
// HEALTH CHECK (Optional)
// ============================================================================

// Write a heartbeat file that monitoring systems can check
$heartbeatFile = sys_get_temp_dir() . "/proto-worker-{$queueName}.heartbeat";
register_shutdown_function(function() use ($heartbeatFile) {
    if (file_exists($heartbeatFile)) {
        @unlink($heartbeatFile);
    }
});

function updateHeartbeat(string $file): void
{
    file_put_contents($file, json_encode([
        'timestamp' => time(),
        'pid' => getmypid(),
        'memory' => memory_get_usage(true),
    ]));
}

// ============================================================================
// ERROR HANDLER
// ============================================================================

set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    workerLog("PHP Error: {$message} in {$file}:{$line}", 'ERROR');
});

set_exception_handler(function($exception) {
    workerLog("Uncaught exception: " . $exception->getMessage(), 'CRITICAL');
    workerLog("Stack trace: " . $exception->getTraceAsString(), 'CRITICAL');
    exit(1);
});

// ============================================================================
// MEMORY LIMIT CHECK
// ============================================================================

function checkMemoryLimit(int $limitMB): bool
{
    $currentMB = memory_get_usage(true) / 1024 / 1024;
    if ($currentMB > $limitMB) {
        workerLog("Memory usage ({$currentMB}MB) exceeded limit ({$limitMB}MB)", 'WARNING');
        return false;
    }
    return true;
}

// ============================================================================
// MAIN WORKER LOOP
// ============================================================================

workerLog("Starting worker...");
workerLog("Queue: {$queueName}");
workerLog("Max jobs: " . ($maxJobs > 0 ? $maxJobs : 'unlimited'));
workerLog("Driver: " . Jobs::queue()->getConfig()['driver']);
workerLog("Memory limit: " . Jobs::queue()->getConfig()['memory_limit'] . "MB");
workerLog("PID: " . getmypid());

try {
    $startTime = time();
    $processedCount = 0;

    // Main worker loop
    while (!$shutdownRequested) {
        // Handle signals
        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }

        // Update heartbeat
        updateHeartbeat($heartbeatFile);

        // Check memory limit
        $memoryLimit = Jobs::queue()->getConfig()['memory_limit'];
        if (!checkMemoryLimit($memoryLimit)) {
            workerLog("Stopping due to memory limit");
            break;
        }

        // Process one job
        Jobs::work($queueName, 1);
        $processedCount++;

        // Check if we've reached max jobs
        if ($maxJobs > 0 && $processedCount >= $maxJobs) {
            workerLog("Reached max jobs limit ({$maxJobs}), stopping");
            break;
        }

        // Sleep between jobs (if configured)
        $sleepTime = Jobs::queue()->getConfig()['sleep'] ?? 3;
        sleep($sleepTime);
    }

    $duration = time() - $startTime;
    $rate = $duration > 0 ? number_format($processedCount / $duration, 2) : 0;
    workerLog("Worker stopped after processing {$processedCount} jobs in {$duration}s ({$rate} jobs/sec)");

} catch (\Exception $e) {
    workerLog("Worker crashed: " . $e->getMessage(), 'CRITICAL');
    workerLog("Stack trace: " . $e->getTraceAsString(), 'CRITICAL');
    exit(1);
}

// ============================================================================
// CLEANUP
// ============================================================================

workerLog("Worker shutdown complete");
exit(0);
