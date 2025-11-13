# Redis Events - Quick Reference

## Basic Usage

### Local Events (In-Process)
```php
// Subscribe
$token = events()->subscribe('event.name', fn($data) => /* handle */);

// Emit
events()->emit('event.name', ['key' => 'value']);

// Unsubscribe
events()->unsubscribe('event.name', $token);
```

### Redis Events (Distributed)
```php
// Subscribe
$token = events()->subscribe('redis:event.name', fn($data) => /* handle */);

// Emit (broadcasts to all instances)
events()->emit('redis:event.name', ['key' => 'value']);

// Unsubscribe
events()->unsubscribe('redis:event.name', $token);
```

## Static API

```php
use Proto\Events\Events;

// Subscribe
Events::on('redis:channel', $callback);

// Publish
Events::update('redis:channel', $data);

// Unsubscribe
Events::off('redis:channel', $token);
```

## SSE (Server-Sent Events)

```php
$conversationId = 1;

// Subscribe to conversation's message updates channel
$channel = "conversation:{$conversationId}:messages";
redisEvent($channel, function($channel, $message): array|null
{
  // Message contains message ID from Redis publish
  $messageId = $message['id'] ?? $message['messageId'] ?? null;
  if (!$messageId)
  {
    return null;
  }

  $action = $message['action'] ?? 'merge';
  if ($action === 'delete')
  {
    return [
      'merge' => [],
      'deleted' => [$messageId]
    ];
  }

  // Fetch the updated message data
  $messageData = Message::get($messageId);
  if (!$messageData)
  {
    // Message not found
    return null;
  }

  return [
    'merge' => [$messageData],
    'deleted' => []
  ];
});
```

## Common Patterns

### User Notifications
```php
// Subscribe
events()->subscribe("redis:user:{$userId}:notifications", $callback);

// Publish
events()->emit("redis:user:{$userId}:notifications", $notification);
```

### Chat Rooms
```php
// Subscribe to room
events()->subscribe("redis:chat.room.{$roomId}", $callback);

// Send message
events()->emit("redis:chat.room.{$roomId}", $message);
```

### System Broadcasts
```php
// All instances receive this
events()->emit('redis:system.broadcast', $announcement);
```

### Job Progress
```php
// Update progress
events()->emit("redis:job.{$jobId}.progress", [
    'percent' => 50,
    'status' => 'Processing...'
]);
```

## Configuration

In `common/Config/.env`:
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

## Direct Redis Access

```php
use Proto\Cache\Cache;

$cache = Cache::getInstance();
$redis = $cache->getDriver();

// Publish
$redis->publish('channel', 'message');

// Subscribe (blocking)
$redis->subscribe(['channel'], function($channel, $message) {
    // Handle message
});

// Pattern subscribe
$redis->psubscribe(['user:*'], function($pattern, $channel, $message) {
    // Handle message
});
```

## Tips

1. **Prefix Redis events**: Always use `redis:` for distributed events
2. **JSON encode data**: Complex data should be JSON encoded
3. **Unique channels**: Use specific channel names (e.g., `user:123:notifications`)
4. **Unsubscribe**: Clean up subscriptions to prevent memory leaks
5. **Error handling**: Wrap in try-catch for production
6. **SSE timeouts**: Configure server timeouts for long-running connections

## Architecture

```
Local Event:  events()->emit('event') → PubSub → Local callbacks
Redis Event:  events()->emit('redis:event') → Redis Pub/Sub → All instances
```
