# Proto Jobs Automation Integration

The Jobs system has been integrated with Proto's automation framework, providing structured, benchmarked, and resource-managed job processing routines that can be easily integrated with cron systems.

## Automation Routines

### JobSchedulerRoutine

Processes scheduled jobs by running the job scheduler tick. This routine should be run regularly (typically every minute) via cron to dispatch due scheduled jobs to the job queue.

**Location**: `src/Automation/Processes/Jobs/JobSchedulerRoutine.php`

**Features**:
- Configurable memory and time limits (512M memory, 5 minutes execution time)
- Verbose logging support
- Automatic error handling and logging
- Performance benchmarking
- Integration with Proto's automation framework

**Usage**:
```php
use Proto\Automation\Processes\Jobs\JobSchedulerRoutine;

// Run scheduler routine
$routine = new JobSchedulerRoutine(null, true); // verbose mode
$routine->run();

// Access scheduler and queue instances
$scheduler = $routine->getScheduler();
$queue = $routine->getQueue();
```

### JobWorkerRoutine

Processes jobs from the job queue. This routine can be run to process a specific number of jobs or run continuously as a background worker.

**Location**: `src/Automation/Processes/Jobs/JobWorkerRoutine.php`

**Features**:
- Configurable memory and time limits (1024M memory, 30 minutes execution time)
- Support for specific queues
- Configurable job processing limits
- Verbose logging support
- Automatic error handling and logging
- Performance benchmarking

**Usage**:
```php
use Proto\Automation\Processes\Jobs\JobWorkerRoutine;

// Process default queue, unlimited jobs
$routine = new JobWorkerRoutine();
$routine->run();

// Process specific queue with limits
$routine = new JobWorkerRoutine(null, 'high-priority', 10, true);
$routine->run();

// Configure at runtime
$routine = new JobWorkerRoutine();
$routine->setQueueName('emails')
        ->setMaxJobs(5)
        ->setVerbose(true)
        ->run();
```

### JobCleanupRoutine

Cleans up old completed jobs and failed jobs from the database. This routine helps maintain database performance by removing old job records that are no longer needed.

**Location**: `src/Automation/Processes/Jobs/JobCleanupRoutine.php`

**Features**:
- Configurable memory and time limits (256M memory, 10 minutes execution time)
- Configurable retention periods for completed and failed jobs
- Verbose logging support
- Automatic error handling and logging
- Performance benchmarking

**Usage**:
```php
use Proto\Automation\Processes\Jobs\JobCleanupRoutine;

// Use default retention (7 days completed, 30 days failed)
$routine = new JobCleanupRoutine();
$routine->run();

// Custom retention periods
$routine = new JobCleanupRoutine(null, 3, 14, true); // 3 days completed, 14 days failed, verbose
$routine->run();

// Configure at runtime
$routine = new JobCleanupRoutine();
$routine->setCompletedJobRetention(5)
        ->setFailedJobRetention(21)
        ->setVerbose(true)
        ->run();
```

## Automation Scripts

### automation_scheduler.php

Standalone script that runs the job scheduler using Proto's automation system.

**Usage**:
```bash
# Run scheduler (silent mode)
php automation_scheduler.php

# Run scheduler with verbose output
php automation_scheduler.php --verbose
```

**Cron Setup**:
```bash
# Run every minute
* * * * * php /path/to/automation_scheduler.php >> /var/log/job_scheduler.log 2>&1
```

### automation_worker.php

Standalone script that runs job workers using Proto's automation system.

**Usage**:
```bash
# Process default queue, unlimited jobs
php automation_worker.php

# Process specific queue
php automation_worker.php high-priority

# Process limited number of jobs
php automation_worker.php default 10

# Process with verbose output
php automation_worker.php emails 5 --verbose
```

**Background Worker Setup**:
```bash
# Continuous processing with supervisor/systemd
nohup php automation_worker.php default 0 > /var/log/job_worker.log 2>&1 &
```

### automation_cleanup.php

Standalone script that runs job cleanup using Proto's automation system.

**Usage**:
```bash
# Use default retention periods
php automation_cleanup.php

# Custom retention periods
php automation_cleanup.php 3 14

# With verbose output
php automation_cleanup.php 7 30 --verbose
```

**Cron Setup**:
```bash
# Run daily at 2 AM
0 2 * * * php /path/to/automation_cleanup.php >> /var/log/job_cleanup.log 2>&1
```

## Automation Framework Benefits

### Resource Management

All job automation routines inherit from Proto's automation framework, providing:

- **Memory Limit Control**: Configurable memory limits per routine type
- **Execution Time Limits**: Configurable time limits to prevent runaway processes
- **Automatic Resource Cleanup**: Proper cleanup when processes terminate

### Performance Monitoring

- **Benchmarking**: Automatic execution time measurement for all routines
- **Performance Logging**: Detailed performance metrics for monitoring
- **Resource Usage Tracking**: Memory and time usage monitoring

### Error Handling

- **Exception Management**: Comprehensive error handling and logging
- **Graceful Degradation**: Continues processing other jobs even if individual jobs fail
- **Error Reporting**: Detailed error logging with stack traces

### Security

- **CLI-Only Execution**: Automation processes only run from command line
- **Permission Checking**: Validates execution environment
- **Secure Configuration**: Uses environment-based configuration

## Configuration

### Environment Variables

Configure job automation through environment variables:

```env
# Database configuration (inherited from Proto)
DB_HOST=localhost
DB_NAME=proto
DB_USER=root
DB_PASS=

# Job system specific
JOB_SCHEDULER_MEMORY_LIMIT=512M
JOB_SCHEDULER_TIME_LIMIT=300
JOB_WORKER_MEMORY_LIMIT=1024M
JOB_WORKER_TIME_LIMIT=1800
JOB_CLEANUP_MEMORY_LIMIT=256M
JOB_CLEANUP_TIME_LIMIT=600

# Retention periods
JOB_COMPLETED_RETENTION_DAYS=7
JOB_FAILED_RETENTION_DAYS=30
```

### Routine Customization

Extend the base routines for custom functionality:

```php
<?php declare(strict_types=1);
namespace App\Automation\Processes\Jobs;

use Proto\Automation\Processes\Jobs\JobWorkerRoutine;

class CustomJobWorkerRoutine extends JobWorkerRoutine
{
    protected string $memoryLimit = '2048M';
    protected int $timeLimit = 3600;

    protected function process(): void
    {
        // Custom pre-processing
        $this->customSetup();

        // Run standard job processing
        parent::process();

        // Custom post-processing
        $this->customCleanup();
    }

    private function customSetup(): void
    {
        // Custom initialization logic
    }

    private function customCleanup(): void
    {
        // Custom cleanup logic
    }
}
```

## Integration with Existing Automation

### Using Proto's Process System

Access job routines through Proto's process system:

```php
use Proto\Automation\Process;

// Get routine by class name
$routine = Process::getRoutine('Proto\\Automation\\Processes\\Jobs\\JobSchedulerRoutine');
if ($routine) {
    $routine->run();
}
```

### Combining with Other Routines

Create composite automation processes:

```php
<?php declare(strict_types=1);
namespace App\Automation\Processes;

use Proto\Automation\Processes\Routine;
use Proto\Automation\Processes\Jobs\JobSchedulerRoutine;
use Proto\Automation\Processes\Jobs\JobWorkerRoutine;
use Proto\Automation\Processes\Session\CleanUpSessionRoutine;

class DailyMaintenanceRoutine extends Routine
{
    protected function process(): void
    {
        // Run job scheduler
        $scheduler = new JobSchedulerRoutine(null, $this->verbose);
        $scheduler->run();

        // Process some jobs
        $worker = new JobWorkerRoutine(null, 'maintenance', 50, $this->verbose);
        $worker->run();

        // Clean up sessions
        $sessionCleanup = new CleanUpSessionRoutine();
        $sessionCleanup->run();

        // Custom maintenance tasks
        $this->customMaintenance();
    }

    private function customMaintenance(): void
    {
        // Application-specific maintenance
    }
}
```

## Migration from Standalone Scripts

### From scheduler.php

**Old approach**:
```php
require_once __DIR__ . '/vendor/autoload.php';

use Proto\Jobs\JobQueue;
use Proto\Jobs\Scheduler;
use Proto\Jobs\Drivers\DatabaseDriver;

$driver = new DatabaseDriver();
$queue = new JobQueue([], $driver);
$scheduler = new Scheduler($queue);
$dueJobs = $scheduler->tick();
```

**New automation approach**:
```php
require_once __DIR__ . '/vendor/autoload.php';

use Proto\Automation\Processes\Jobs\JobSchedulerRoutine;

$routine = new JobSchedulerRoutine(null, $verbose);
$routine->run();
```

### From job_worker.php

**Old approach**:
```php
$driver = new DatabaseDriver();
$queue = new JobQueue([], $driver);

while (true) {
    $queue->work($queueName, 1);
    sleep(3);
}
```

**New automation approach**:
```php
use Proto\Automation\Processes\Jobs\JobWorkerRoutine;

$routine = new JobWorkerRoutine(null, $queueName, $maxJobs, $verbose);
$routine->run();
```

## Monitoring and Logging

### Log Files

The automation system generates structured logs:

```
[2025-08-14 10:00:01] Proto Job Scheduler: Dispatched 5 due jobs
[2025-08-14 10:00:01] Proto Job Worker: Processed 10 jobs from 'default' queue
[2025-08-14 02:00:01] Proto Job Cleanup: Removed 150 old job records (100 completed, 50 failed)
```

### Performance Metrics

Access performance data from routines:

```php
$routine = new JobWorkerRoutine();
$routine->run();

echo "Execution time: " . $routine->benchmark->getTotal() . " seconds\n";
echo "Status: " . $routine->benchmark->getStatus() . "\n";
```

### Health Monitoring

Create monitoring scripts:

```php
<?php
use Proto\Jobs\Drivers\DatabaseDriver;

$driver = new DatabaseDriver();
$stats = $driver->getStats();

// Alert if too many failed jobs
if ($stats['failed'] > 100) {
    // Send alert
}

// Alert if queue is backing up
if ($stats['pending'] > 1000) {
    // Send alert
}
```

## Best Practices

1. **Use Specific Queues**: Process different queue types with different workers
2. **Monitor Resource Usage**: Set appropriate memory and time limits
3. **Implement Graceful Shutdown**: Handle signals properly in long-running processes
4. **Log Everything**: Use verbose mode for debugging, silent mode for production
5. **Regular Cleanup**: Run cleanup routines to maintain database performance
6. **Health Monitoring**: Implement monitoring for queue health and worker status
7. **Error Alerting**: Set up alerts for failed jobs and worker errors

This automation integration provides a robust, scalable foundation for background job processing in Proto applications while maintaining consistency with the framework's architecture and conventions.
