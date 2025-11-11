<?php declare(strict_types=1);

/**
 * Redis Events - Usage Examples
 *
 * This file demonstrates various ways to use the Redis-enabled event system.
 */

// ============================================================================
// Example 1: Basic Local Events (In-Process Only)
// ============================================================================

// Subscribe to a local event
$token = events()->subscribe('order.created', function ($order) {
    echo "Order #{$order['id']} created locally\n";
});

// Emit the local event
events()->emit('order.created', ['id' => 123, 'total' => 99.99]);

// Unsubscribe when done
events()->unsubscribe('order.created', $token);


// ============================================================================
// Example 2: Distributed Redis Events (Cross-Instance)
// ============================================================================

// Subscribe to a Redis event (works across all app instances)
$redisToken = events()->subscribe('redis:order.created', function ($order) {
    // This will be called on ALL instances that subscribe to this event
    echo "Order #{$order['id']} created (via Redis)\n";

    // Send email notification, update analytics, etc.
});

// Publish to Redis - all subscribers across all instances receive this
events()->emit('redis:order.created', [
    'id' => 456,
    'total' => 199.99,
    'user_id' => 789,
    'timestamp' => time()
]);


// ============================================================================
// Example 3: Real-Time Notifications via SSE
// ============================================================================

use Proto\Events\RedisAsyncEvent;
use Proto\Http\Loop\EventLoop;

function streamNotifications(int $userId): void
{
    // Set SSE headers
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');

    // Create event loop
    $loop = new EventLoop(tickInterval: 50);

    // Subscribe to user-specific notifications channel
    $redisEvent = new RedisAsyncEvent(
        channels: "user:{$userId}:notifications",
        callback: function ($channel, $message) {
            // Send to client via SSE
            echo "event: notification\n";
            echo "data: " . json_encode($message) . "\n\n";
            ob_flush();
            flush();
        }
    );

    $loop->addEvent($redisEvent);

    // Keep alive ping every 30 seconds
    $lastPing = time();
    $pingEvent = new class($lastPing) implements \Proto\Http\Loop\EventInterface {
        public function __construct(private int $lastPing) {}

        public function tick(): void {
            if (time() - $this->lastPing >= 30) {
                echo ": ping\n\n";
                ob_flush();
                flush();
                $this->lastPing = time();
            }
        }
    };

    $loop->addEvent($pingEvent);

    // Start the loop
    $loop->loop();
}


// ============================================================================
// Example 4: Multi-Channel Subscriptions
// ============================================================================

use Proto\Events\RedisAsyncEvent;
use Proto\Http\Loop\EventLoop;

function streamMultipleChannels(array $channels): void
{
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');

    $loop = new EventLoop();

    // Subscribe to multiple channels at once
    $redisEvent = new RedisAsyncEvent(
        channels: $channels, // e.g., ['news', 'sports', 'weather']
        callback: function ($channel, $message) {
            echo "event: {$channel}\n";
            echo "data: " . json_encode($message) . "\n\n";
            ob_flush();
            flush();
        }
    );

    $loop->addEvent($redisEvent);
    $loop->loop();
}


// ============================================================================
// Example 5: Chat Application
// ============================================================================

class ChatService
{
    /**
     * Send a message to a chat room
     */
    public function sendMessage(string $roomId, array $message): void
    {
        events()->emit("redis:chat.room.{$roomId}", [
            'id' => uniqid(),
            'room_id' => $roomId,
            'user_id' => $message['user_id'],
            'username' => $message['username'],
            'text' => $message['text'],
            'timestamp' => time()
        ]);
    }

    /**
     * Stream messages for a chat room
     */
    public function streamRoom(string $roomId): void
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');

        $loop = new EventLoop(tickInterval: 25);

        $redisEvent = new RedisAsyncEvent(
            channels: "chat.room.{$roomId}",
            callback: function ($channel, $message) {
                echo "event: message\n";
                echo "data: " . json_encode($message) . "\n\n";
                ob_flush();
                flush();
            }
        );

        $loop->addEvent($redisEvent);
        $loop->loop();
    }
}


// ============================================================================
// Example 6: Job Progress Updates
// ============================================================================

class JobProgressTracker
{
    /**
     * Update job progress
     */
    public function updateProgress(string $jobId, int $percent, string $status): void
    {
        events()->emit("redis:job.{$jobId}.progress", [
            'job_id' => $jobId,
            'percent' => $percent,
            'status' => $status,
            'timestamp' => time()
        ]);
    }

    /**
     * Stream job progress to client
     */
    public function streamProgress(string $jobId): void
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');

        $loop = new EventLoop();

        $redisEvent = new RedisAsyncEvent(
            channels: "job.{$jobId}.progress",
            callback: function ($channel, $message) use ($jobId) {
                echo "event: progress\n";
                echo "data: " . json_encode($message) . "\n\n";
                ob_flush();
                flush();

                // Auto-close when job complete
                if ($message['percent'] >= 100) {
                    echo "event: complete\n";
                    echo "data: {\"status\":\"done\"}\n\n";
                    ob_flush();
                    flush();
                    exit;
                }
            }
        );

        $loop->addEvent($redisEvent);
        $loop->loop();
    }
}


// ============================================================================
// Example 7: System-Wide Broadcasts
// ============================================================================

// Subscribe all instances to system announcements
events()->subscribe('redis:system.broadcast', function ($announcement) {
    // Log to all instances
    error_log("SYSTEM BROADCAST: " . $announcement['message']);

    // Could also clear caches, reload config, etc.
    if ($announcement['action'] === 'clear_cache') {
        \Proto\Cache\Cache::getInstance()->clear();
    }
});

// Send a broadcast from admin panel
function broadcastToAllInstances(string $message, string $action = 'info'): void
{
    events()->emit('redis:system.broadcast', [
        'message' => $message,
        'action' => $action,
        'timestamp' => time(),
        'sender' => $_SERVER['SERVER_NAME'] ?? 'unknown'
    ]);
}


// ============================================================================
// Example 8: Live Dashboard Updates
// ============================================================================

class DashboardMetrics
{
    /**
     * Publish metrics update
     */
    public function publishMetrics(array $metrics): void
    {
        events()->emit('redis:dashboard.metrics', [
            'active_users' => $metrics['active_users'],
            'requests_per_minute' => $metrics['rpm'],
            'error_rate' => $metrics['error_rate'],
            'timestamp' => time()
        ]);
    }

    /**
     * Stream dashboard updates via SSE
     */
    public function streamMetrics(): void
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');

        $loop = new EventLoop(tickInterval: 100);

        $redisEvent = new RedisAsyncEvent(
            channels: 'dashboard.metrics',
            callback: function ($channel, $metrics) {
                echo "event: metrics\n";
                echo "data: " . json_encode($metrics) . "\n\n";
                ob_flush();
                flush();
            }
        );

        $loop->addEvent($redisEvent);
        $loop->loop();
    }
}


// ============================================================================
// Example 9: Pattern-Based Subscriptions (Advanced)
// ============================================================================

use Proto\Cache\Cache;

function subscribeToUserPatterns(): void
{
    $cache = Cache::getInstance();
    $redis = $cache->getDriver();

    if ($redis instanceof \Proto\Cache\Drivers\RedisDriver) {
        // Subscribe to all user events using wildcard
        $redis->psubscribe('user:*:*', function ($pattern, $channel, $message) {
            echo "Received on pattern '{$pattern}' from channel '{$channel}': {$message}\n";

            // Parse channel name
            $parts = explode(':', $channel);
            $userId = $parts[1] ?? null;
            $eventType = $parts[2] ?? null;

            // Route based on event type
            match($eventType) {
                'login' => handleUserLogin($userId, $message),
                'logout' => handleUserLogout($userId, $message),
                'update' => handleUserUpdate($userId, $message),
                default => null
            };
        });
    }
}


// ============================================================================
// Example 10: Graceful Shutdown
// ============================================================================

class EventStreamManager
{
    private EventLoop $loop;
    private array $events = [];

    public function addStream(RedisAsyncEvent $event): void
    {
        $this->events[] = $event;
        $this->loop->addEvent($event);
    }

    public function start(): void
    {
        // Handle shutdown signals
        pcntl_signal(SIGTERM, [$this, 'shutdown']);
        pcntl_signal(SIGINT, [$this, 'shutdown']);

        $this->loop->loop();
    }

    public function shutdown(): void
    {
        // Gracefully terminate all Redis events
        foreach ($this->events as $event) {
            $event->terminate();
        }

        $this->loop->end();
        exit(0);
    }
}
