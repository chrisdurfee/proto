# Redis Event System Integration

This document describes how to use the Redis pub/sub functionality integrated with Proto's global event system.

## Overview

The Proto framework now supports distributed event-driven architecture using Redis pub/sub. Events can be routed automatically to either local (in-process) or distributed (Redis) pub/sub systems based on a simple naming convention.

## Key Features

- **Unified API**: Use the same `Events` class for both local and Redis events
- **Automatic Routing**: Events with the `redis:` prefix are automatically routed to Redis pub/sub
- **Async Support**: `RedisAsyncEvent` integrates with `EventLoop` for SSE and real-time streaming
- **Backward Compatible**: Existing local events continue to work without changes

## Installation & Configuration

Ensure Redis is configured in your `common/Config/.env`:

```json
{
  "cache": {
    "driver": "redis",
    "connection": {
      "host": "127.0.0.1",
      "port": 6379,
      "password": ""
    }
  }
}
```

## Basic Usage

### Using the Global Helper

```php
// Subscribe to a local event
events()->subscribe('user.created', function ($user) {
    echo "Local: User created - {$user['name']}";
});

// Emit a local event
events()->emit('user.created', ['name' => 'John Doe']);
```

### Using Redis Events (Distributed)

Simply prefix your event key with `redis:`:

```php
// Subscribe to a Redis event (works across multiple application instances)
$token = events()->subscribe('redis:user.created', function ($user) {
    echo "Redis: User created - {$user['name']}";
});

// Publish to Redis (all subscribers across all instances will receive this)
events()->emit('redis:user.created', ['name' => 'Jane Smith']);

// Unsubscribe when done
events()->unsubscribe('redis:user.created', $token);
```

### Using Static Methods

```php
use Proto\Events\Events;

// Subscribe
$token = Events::on('redis:notification', function ($data) {
    // Handle notification
});

// Publish
Events::update('redis:notification', ['message' => 'Hello World']);

// Unsubscribe
Events::off('redis:notification', $token);
```

## Server-Sent Events (SSE) Integration

For real-time streaming applications, use `RedisAsyncEvent` with the `EventLoop`:

```php
use Proto\Events\RedisAsyncEvent;
use Proto\Http\Loop\EventLoop;

// Create an event loop
$loop = new EventLoop();

// Create a Redis async event for SSE
$redisEvent = new RedisAsyncEvent(
    channels: ['notifications', 'updates'],
    callback: function ($channel, $message) {
        // Send SSE message to client
        return [
            'channel' => $channel,
            'message' => $message
        ];
    }
);

// Add to event loop
$loop->addEvent($redisEvent);

// Set SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Start the loop (blocks until connection closes)
$loop->loop();
```

## Controller Example (SSE Endpoint)

```php
namespace Modules\Notifications\Controllers;

use Proto\Controllers\ApiController;
use Proto\Http\Router\Request;
use Proto\Events\RedisAsyncEvent;
use Proto\Http\Loop\EventLoop;

class NotificationController extends ApiController
{
    /**
     * SSE endpoint for real-time notifications
     */
    public function stream(Request $req): void
    {
        // Set SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable nginx buffering

        // Get user ID from authentication
        $userId = $req->input('user_id');

        // Create event loop
        $loop = new EventLoop(tickInterval: 50);

        // Subscribe to user-specific Redis channel
        $redisEvent = new RedisAsyncEvent(
            channels: "user:{$userId}:notifications",
            callback: function ($channel, $message)
            {
                echo "event: notification\n";
                return $message;
            }
        );

        $loop->addEvent($redisEvent);

        // Send initial connection message
        echo "event: connected\n";
        echo "data: {\"status\":\"connected\"}\n\n";
        ob_flush();
        flush();

        // Start the event loop
        $loop->loop();
    }
}
```

## Publishing to Redis from Other Services

You can publish events from any part of your application:

```php
// In a user registration handler
events()->emit('redis:user.registered', [
    'id' => $user->id,
    'email' => $user->email,
    'timestamp' => time()
]);

// In a notification service
events()->emit("redis:user:{$userId}:notifications", [
    'type' => 'message',
    'title' => 'New Message',
    'body' => 'You have a new message',
    'timestamp' => time()
]);
```

## Pattern Matching

Use Redis pattern matching for flexible subscriptions:

```php
use Proto\Cache\Cache;

$cache = Cache::getInstance();
$redis = $cache->getDriver();

if ($redis instanceof \Proto\Cache\Drivers\RedisDriver) {
    // Subscribe to all user notifications using pattern
    $redis->psubscribe('user:*:notifications', function ($pattern, $channel, $message) {
        echo "Pattern: {$pattern}, Channel: {$channel}, Message: {$message}\n";
    });
}
```

## Advanced Usage

### Direct Redis Adapter Access

For more control, access the Redis adapter directly:

```php
$adapter = events()->getRedisAdapter();

if ($adapter !== null) {
    // Publish directly
    $adapter->publish('my-channel', ['data' => 'value']);

    // Subscribe
    $token = $adapter->subscribe('my-channel', function ($message) {
        // Handle message
    });

    // Check if listening
    if (!$adapter->isListening()) {
        $adapter->startListening(); // Blocking operation
    }
}
```

### Custom Redis Connection

Create a `RedisAsyncEvent` with custom settings:

```php
$redisEvent = new RedisAsyncEvent(
    channels: ['channel1', 'channel2'],
    callback: function ($channel, $message) {
        // Handle message
    },
    settings: [
        'host' => 'redis.example.com',
        'port' => 6380,
        'password' => 'secret'
    ]
);
```

## Best Practices

1. **Use `redis:` prefix for distributed events**: Only events that need to be shared across multiple instances should use the Redis prefix
2. **Clean up subscriptions**: Always unsubscribe when done to prevent memory leaks
3. **Handle connection failures**: Wrap Redis operations in try-catch blocks
4. **Use specific channel names**: Avoid overly broad channel patterns that could cause performance issues
5. **JSON encode complex data**: Redis only supports string messages, so encode arrays/objects as JSON
6. **Set appropriate timeouts**: For SSE endpoints, ensure server timeout settings allow long-running connections

## Architecture Notes

### How It Works

1. The `Events` class checks if an event key starts with `redis:`
2. If yes, it strips the prefix and routes to `RedisPubSubAdapter`
3. If no, it uses the local `PubSub` system
4. The `RedisPubSubAdapter` manages subscriptions and uses `RedisDriver` for actual pub/sub
5. `RedisAsyncEvent` creates a dedicated Redis connection for non-blocking operations in the event loop

### Event Flow

```
Application Code
      ↓
events()->emit('redis:event')
      ↓
Events::emit()
      ↓
isRedisEvent()? → Yes
      ↓
RedisPubSubAdapter::publish()
      ↓
RedisDriver::publish()
      ↓
Redis Server
      ↓
All Subscribed Instances
```

## Troubleshooting

### Events not being received

- Verify Redis is running and accessible
- Check that `cache.driver` is set to `redis` in `.env`
- Ensure channel names match exactly (case-sensitive)
- Confirm subscriptions are set up before publishing

### SSE connection drops

- Check server timeout settings (PHP, Nginx, Apache)
- Ensure client reconnection logic is in place
- Verify no buffering is happening at the web server level
- Use appropriate tick intervals in EventLoop

### Performance issues

- Limit the number of channels subscribed to
- Use specific channel names instead of broad patterns
- Consider connection pooling for high-traffic applications
- Monitor Redis memory usage

## Migration Guide

### From Local to Redis Events

```php
// Old (local events only)
events()->emit('user.created', $userData);

// New (distributed via Redis)
events()->emit('redis:user.created', $userData);
```

No other code changes required! The same API works for both.
