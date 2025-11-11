# Redis SSE Examples

This document shows how to use the new `redisEvent()` helper function for creating Redis-powered Server-Sent Events streams.

## Overview

The `redisEvent()` function provides a simple way to create SSE endpoints that stream messages from Redis pub/sub channels. It works similarly to `serverEvent()` but uses Redis for distributed real-time messaging.

### Why a Separate Redis Connection?

`RedisAsyncEvent` creates its own Redis connection because Redis's `SUBSCRIBE` command is **blocking** and puts the connection into a special "pub/sub mode" where you can only execute pub/sub commands. This would block your main cache driver if we reused the same connection. The dedicated connection allows:

- Non-blocking pub/sub operations in the event loop
- Main cache driver remains available for other operations
- Proper isolation between caching and streaming

## Basic Usage

### Simple Message Streaming

```php
// In your controller or route
router()->get('stream/notifications', function($req) {
    $userId = $req->input('user_id');

    // Stream all messages from user's notification channel
    redisEvent("user:{$userId}:notifications");
});

// Publish a notification elsewhere in your app
events()->emit("redis:user:123:notifications", [
    'type' => 'message',
    'title' => 'New Message',
    'body' => 'You have a new message'
]);
```

### With Message Processing

```php
router()->get('stream/orders', function($req) {
    // Process messages before sending to client
    redisEvent('orders', function($channel, $message, $event) {
        // Transform the message
        $formatted = [
            'event' => 'order',
            'data' => $message,
            'timestamp' => time()
        ];

        // Return value is sent as SSE message
        return $formatted;
    });
});
```

### Multiple Channels

```php
router()->get('stream/dashboard', function($req) {
    // Subscribe to multiple channels at once
    redisEvent(['orders', 'payments', 'shipments'], function($channel, $message, $event) {
        // Handle different event types
        return match($channel) {
            'orders' => ['type' => 'order', 'data' => $message],
            'payments' => ['type' => 'payment', 'data' => $message],
            'shipments' => ['type' => 'shipment', 'data' => $message],
        };
    });
});
```

## Complete Examples

### User Notifications

```php
namespace Modules\Notifications\Controllers;

use Proto\Controllers\ApiController;
use Proto\Http\Router\Request;

class NotificationController extends ApiController
{
    /**
     * Stream real-time notifications to user
     */
    public function stream(Request $req): void
    {
        $userId = $req->input('user_id');

        // Validate user
        if (!$this->validateUser($userId)) {
            $this->error('Unauthorized', 401);
            return;
        }

        // Stream notifications
        redisEvent("user:{$userId}:notifications", function($channel, $message, $event) {
            // Add server timestamp
            $message['server_time'] = time();

            // You can access event methods
            // $event->terminate() to stop streaming

            return $message;
        });
    }

    /**
     * Send a notification (called from other parts of the app)
     */
    public function send(Request $req): void
    {
        $userId = $req->input('user_id');
        $notification = $req->json();

        // Broadcast to Redis - all connected clients receive it
        events()->emit("redis:user:{$userId}:notifications", $notification);

        $this->success(['sent' => true]);
    }
}
```

### Live Chat

```php
namespace Modules\Chat\Controllers;

class ChatController extends ApiController
{
    /**
     * Stream chat messages for a room
     */
    public function stream(Request $req): void
    {
        $roomId = $req->input('room_id');

        redisEvent("chat:room:{$roomId}", function($channel, $message, $event) use ($roomId) {
            // Log message
            ChatLogger::log($roomId, $message);

            // Format for client
            return [
                'id' => uniqid(),
                'room_id' => $roomId,
                'username' => $message['username'],
                'text' => $message['text'],
                'timestamp' => time()
            ];
        });
    }

    /**
     * Send a chat message
     */
    public function send(Request $req): void
    {
        $roomId = $req->input('room_id');
        $message = $req->json();

        // Broadcast to all connected clients in the room
        events()->emit("redis:chat:room:{$roomId}", $message);

        $this->success(['sent' => true]);
    }
}
```

### Job Progress Tracking

```php
namespace Modules\Jobs\Controllers;

class JobController extends ApiController
{
    /**
     * Stream job progress updates
     */
    public function streamProgress(Request $req): void
    {
        $jobId = $req->input('job_id');

        redisEvent("job:{$jobId}:progress", function($channel, $message, $event) {
            // Auto-terminate when job completes
            if ($message['status'] === 'completed' || $message['status'] === 'failed') {
                // Send final message
                $result = [
                    'status' => $message['status'],
                    'percent' => 100,
                    'message' => $message['message'] ?? 'Done'
                ];

                // Return false to terminate stream
                // But first return the final message
                $event->message($result);
                return false;
            }

            return $message;
        }, interval: 100); // Check every 100ms for faster updates
    }

    /**
     * Update job progress (called by worker)
     */
    public function updateProgress(Request $req): void
    {
        $jobId = $req->input('job_id');
        $progress = $req->json();

        // Broadcast progress to all watchers
        events()->emit("redis:job:{$jobId}:progress", [
            'percent' => $progress['percent'],
            'status' => $progress['status'],
            'message' => $progress['message'] ?? null
        ]);

        $this->success(['updated' => true]);
    }
}
```

### Live Dashboard Metrics

```php
namespace Modules\Dashboard\Controllers;

class MetricsController extends ApiController
{
    /**
     * Stream real-time metrics
     */
    public function stream(Request $req): void
    {
        // Subscribe to multiple metric channels
        redisEvent(
            channels: ['metrics:users', 'metrics:orders', 'metrics:performance'],
            callback: function($channel, $message, $event) {
                // Parse metric type from channel
                $type = str_replace('metrics:', '', $channel);

                return [
                    'metric' => $type,
                    'value' => $message['value'],
                    'timestamp' => $message['timestamp'] ?? time()
                ];
            },
            interval: 200 // Update every 200ms
        );
    }

    /**
     * Publish metrics (called by monitoring service)
     */
    public function publishMetrics(): void
    {
        // This would typically be called by a background service
        $metrics = $this->gatherMetrics();

        foreach ($metrics as $type => $value) {
            events()->emit("redis:metrics:{$type}", [
                'value' => $value,
                'timestamp' => time()
            ]);
        }
    }
}
```

## Advanced Patterns

### Conditional Streaming

```php
redisEvent('events', function($channel, $message, $event) {
    // Filter messages based on criteria
    if ($message['priority'] < 5) {
        return null; // Don't send low-priority messages
    }

    // Send high-priority messages
    return $message;
});
```

### Rate Limiting

```php
redisEvent('updates', function($channel, $message, $event) use ($req) {
    static $lastSent = 0;
    $now = microtime(true);

    // Rate limit: max 1 message per second
    if ($now - $lastSent < 1.0) {
        return null; // Skip this message
    }

    $lastSent = $now;
    return $message;
});
```

### Message Aggregation

```php
redisEvent(['channel1', 'channel2', 'channel3'], function($channel, $message, $event) {
    static $buffer = [];
    static $lastFlush = 0;

    // Buffer messages
    $buffer[] = ['channel' => $channel, 'data' => $message];

    // Flush every 5 seconds or when buffer is full
    $now = time();
    if (count($buffer) >= 10 || $now - $lastFlush >= 5) {
        $batch = $buffer;
        $buffer = [];
        $lastFlush = $now;

        return ['batch' => $batch, 'count' => count($batch)];
    }

    return null; // Don't send individual messages
});
```

### Timeout/Heartbeat

```php
redisEvent('notifications', function($channel, $message, $event) {
    static $lastMessage = null;
    static $startTime = null;

    if ($startTime === null) {
        $startTime = time();
    }

    // Terminate after 30 minutes of streaming
    if (time() - $startTime > 1800) {
        return false; // Terminate
    }

    // Send periodic heartbeat if no messages
    $now = time();
    if ($lastMessage === null || $now - $lastMessage > 30) {
        $event->message(['type' => 'heartbeat', 'timestamp' => $now]);
    }

    $lastMessage = $now;
    return $message;
});
```

## Client-Side JavaScript

### Basic Connection

```javascript
const userId = 123;
const eventSource = new EventSource(`/api/stream/notifications?user_id=${userId}`);

eventSource.onmessage = function(event) {
    const data = JSON.parse(event.data);
    console.log('Notification:', data);

    // Update UI
    showNotification(data);
};

eventSource.onerror = function(error) {
    console.error('SSE Error:', error);
    eventSource.close();
};
```

### Multiple Event Types

```javascript
const eventSource = new EventSource('/api/stream/dashboard');

// Listen for specific event types
eventSource.addEventListener('order', function(event) {
    const order = JSON.parse(event.data);
    updateOrdersWidget(order);
});

eventSource.addEventListener('payment', function(event) {
    const payment = JSON.parse(event.data);
    updatePaymentsWidget(payment);
});

// Reconnect on error
eventSource.onerror = function() {
    setTimeout(() => {
        location.reload(); // Simple reconnect
    }, 5000);
};
```

### With Heartbeat Handling

```javascript
let lastHeartbeat = Date.now();

const eventSource = new EventSource('/api/stream/notifications');

eventSource.onmessage = function(event) {
    const data = JSON.parse(event.data);

    if (data.type === 'heartbeat') {
        lastHeartbeat = Date.now();
        return;
    }

    handleNotification(data);
};

// Check for stale connection
setInterval(() => {
    if (Date.now() - lastHeartbeat > 60000) {
        console.log('Connection stale, reconnecting...');
        eventSource.close();
        location.reload();
    }
}, 30000);
```

## Comparison: serverEvent() vs redisEvent()

### serverEvent() - Periodic Updates

```php
// Polls/generates data at regular intervals
serverEvent(interval: 1000, function($event) {
    // Query database or generate data
    $data = $this->getLatestData();

    if (empty($data)) {
        return null; // Don't send anything
    }

    return $data; // Send to client
});
```

### redisEvent() - Event-Driven Updates

```php
// Only sends when Redis messages arrive
redisEvent('updates', function($channel, $message, $event) {
    // Message arrived from Redis
    return $message; // Send to client
});

// Elsewhere in your app
events()->emit('redis:updates', $newData);
```

### When to Use Each

**Use `serverEvent()` when:**
- Polling databases or APIs
- Generating computed data periodically
- Data source is local to the instance
- You control the update frequency

**Use `redisEvent()` when:**
- Streaming real-time events
- Multiple instances need to broadcast
- Event-driven architecture
- Unknown update frequency (depends on events)

## Best Practices

1. **Always validate users**: Check authentication before streaming
2. **Set timeouts**: Use termination logic to prevent infinite streams
3. **Handle errors**: Wrap in try-catch and gracefully terminate on errors
4. **Use specific channels**: Avoid overly broad channel patterns
5. **Monitor Redis memory**: Track pub/sub usage in production
6. **Implement reconnection**: Client should handle disconnections
7. **Add heartbeats**: Send periodic pings for connection health
8. **Rate limit if needed**: Prevent overwhelming clients with messages

## Troubleshooting

### Messages not received

- Check Redis is running and accessible
- Verify channel names match exactly (case-sensitive)
- Ensure `cache.driver` is set to `redis` in config
- Check that publish happens after subscribe

### High memory usage

- Limit number of simultaneous connections
- Set connection timeouts
- Clear old subscriptions
- Monitor Redis with `INFO` command

### Connection drops

- Check server timeout settings (PHP, Nginx, Apache)
- Implement client reconnection logic
- Add heartbeat messages
- Verify network stability

## Architecture Notes

The flow looks like this:

```
Publisher (any instance)
    ↓
events()->emit('redis:channel', $data)
    ↓
Redis Pub/Sub
    ↓
All Subscribed Instances
    ↓
RedisAsyncEvent (dedicated connection)
    ↓
EventLoop (non-blocking)
    ↓
SSE Client (browser)
```

The dedicated Redis connection in `RedisAsyncEvent` ensures that:
- Pub/sub doesn't block the main cache driver
- Event loop can process messages efficiently
- Multiple channels can be handled simultaneously
- Graceful cleanup on termination
