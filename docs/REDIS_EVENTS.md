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

### SSE Configuration (optional)

All defaults are safe — you only need this block if you want to tune
behaviour for your workload. Add an `sse` object to the same `.env`:

```json
{
  "sse": {
    "maxDuration": 300,
    "heartbeatInterval": 15,
    "redisReadTimeout": 2,
    "maxReconnectFailures": 3,
    "shutdownGrace": 30
  }
}
```

| Key                    | Default | Purpose                                                                                                                                                       |
| ---------------------- | ------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `maxDuration`          | `300`s  | Hard ceiling on how long any single SSE stream lives. The server cleanly ends the stream when this elapses — the browser's `EventSource` then auto-reconnects. |
| `heartbeatInterval`    | `15`s   | Upper bound on how often Redis `subscribe()` returns control so a heartbeat can be written and the client liveness checked.                                    |
| `redisReadTimeout`     | `2`s    | Per-Redis-read timeout. Capped at `heartbeatInterval`.                                                                                                         |
| `maxReconnectFailures` | `3`     | Consecutive Redis reconnect failures before giving up and ending the stream.                                                                                   |
| `shutdownGrace`        | `30`s   | Extra seconds added on top of `maxDuration` when calling `set_time_limit()`. Cleanup code gets this much runway before PHP forcibly aborts.                    |

Per-stream overrides are also supported:

```php
// Helper functions accept a config array as the trailing argument:
redisEvent('user:42:notifications', $callback, ['maxDuration' => 600]);
serverEvent(20, $callback, ['maxDuration' => 60]);

// Or pass to the constructor directly:
$server = new \Proto\Http\ServerEvents\RedisServerEvents(null, [
    'maxDuration' => 600,
    'heartbeatInterval' => 10,
]);

// Or build a SseConfig once and reuse it:
$cfg = new \Proto\Http\ServerEvents\SseConfig(['maxDuration' => 120]);
$server = new \Proto\Http\ServerEvents\RedisServerEvents(null, $cfg);
```

Controllers using `SyncableTrait` can override `getSyncConfig()`:

```php
protected function getSyncConfig(Request $request): array
{
    return ['maxDuration' => 600, 'heartbeatInterval' => 10];
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

For real-time streaming applications, use `redisEvent`:

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
        // Get user ID from authentication
        $userId = $req->input('user_id');

        // Subscribe to user-specific Redis channel
        $redisEvent = redisEvent("user:{$userId}:notifications", function($channel, $message)
            {
                echo "event: notification\n";
                return $message;
            }
        );
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

## SSE Reliability Model

Long-lived SSE streams over PHP-FPM behind nginx have a notorious failure
mode: a worker enters a blocking subscribe / event loop, the client goes
away (closed tab, mobile Safari freezing, network drop), but the server
never notices because:

- `connection_aborted()` and `connection_status()` are unreliable behind
  nginx / Vite / any reverse proxy that buffers,
- `echo` never throws on a broken pipe, so heartbeats happily write into
  the void forever,
- `ignore_user_abort(true)` (which SSE needs to set) means PHP won't
  auto-abort, and
- `set_time_limit(0)` — historically used by SSE code — removes the only
  remaining safety net.

The result, with `pm.max_children = 100`, is one stuck worker per
abandoned tab until FPM is fully saturated and even `/health.php` queues.

Proto defends against this with **four overlapping bounds**, every one of
which is sufficient on its own:

1. **Bounded stream duration.** Every stream cleanly ends after
   `sse.maxDuration` seconds (default 5 minutes). The browser's
   `EventSource` immediately reconnects, so users never notice. If
   nothing else worked, this alone guarantees workers recycle.
2. **Bounded script time limit.** `set_time_limit(maxDuration + shutdownGrace)`
   means PHP itself will kill the script if the stream code somehow
   ignores the deadline.
3. **Write-failure detection.** All client output (heartbeats and
   events) goes through `Proto\Http\ServerEvents\StreamWriter`, which
   uses `fwrite(php://output, ...)` and surfaces broken pipes as
   boolean failures. The next heartbeat after a client disconnect
   tears the stream down within one `redisReadTimeout` window.
4. **Shutdown handler.** `register_shutdown_function` ensures Redis
   cleanup runs even on fatal errors and FPM `request_terminate_timeout`
   kills (where `__destruct` would not).

You can also explicitly close every active SSE stream for a user
(e.g. on logout, password change, permission revocation):

```php
\Proto\Http\ServerEvents\RedisServerEvents::closeUserConnections($userId);
```

Every active stream subscribes to a user-wide close channel
(`sse:user:close:{userId}`), so a single publish reaps them all across
endpoints.

## PHP-FPM & nginx Recommendations

Proto's defaults work without infra changes, but for production SSE
workloads we strongly recommend the following.

### Dedicated FPM pool for SSE routes

SSE streams hold a worker for their entire lifetime. Putting them in
the same pool as regular API traffic means a burst of stream
reconnections can starve the rest of your API (and your healthcheck).

Create a second pool with its own `max_children` budget and route
SSE-only locations to it via nginx:

```ini
; /etc/php/8.x/fpm/pool.d/sse.conf
[sse]
listen = /run/php/php-fpm-sse.sock
user = www-data
group = www-data
pm = dynamic
pm.max_children = 60
pm.start_servers = 4
pm.min_spare_servers = 4
pm.max_spare_servers = 16
pm.process_idle_timeout = 60s

; Hard backstop. Should be >= sse.maxDuration + a few seconds so
; healthy streams aren't killed mid-flight, but bounded so a stuck
; worker can't camp forever.
request_terminate_timeout = 330s
request_slowlog_timeout = 60s
slowlog = /var/log/php-fpm/sse-slow.log
```

```nginx
# nginx site config
location ~ ^/api/(.*/sync|.*/stream|notifications/.*)$ {
    fastcgi_pass unix:/run/php/php-fpm-sse.sock;
    fastcgi_buffering off;            # required for SSE
    fastcgi_read_timeout 330s;        # >= sse.maxDuration
    proxy_buffering off;
    proxy_read_timeout 330s;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root/index.php;
}

location / {
    fastcgi_pass unix:/run/php/php-fpm.sock;
    fastcgi_read_timeout 60s;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root/index.php;
}
```

### Sizing the bounds

Pick `sse.maxDuration` first, then derive everything else:

| Setting (where)                        | Recommended                            |
| -------------------------------------- | -------------------------------------- |
| `sse.maxDuration` (proto config)       | `300` (5 min) for chat / notifications |
| `request_terminate_timeout` (FPM pool) | `sse.maxDuration + 30`                 |
| `fastcgi_read_timeout` (nginx)         | `>= sse.maxDuration + 30`              |
| `proxy_read_timeout` (nginx)           | `>= sse.maxDuration + 30`              |
| `sse.heartbeatInterval` (proto config) | `15`s — well under any proxy idle cap  |
| `pm.max_children` (FPM pool)           | Peak concurrent SSE clients × 1.2      |

Heartbeats every `heartbeatInterval` seconds keep the connection out of
proxy idle-timeout territory, so you don't need to set the proxy
timeouts especially short.

## Best Practices

1. **Use `redis:` prefix for distributed events**: Only events that need to be shared across multiple instances should use the Redis prefix
2. **Clean up subscriptions**: Always unsubscribe when done to prevent memory leaks
3. **Handle connection failures**: Wrap Redis operations in try-catch blocks
4. **Use specific channel names**: Avoid overly broad channel patterns that could cause performance issues
5. **JSON encode complex data**: Redis only supports string messages, so encode arrays/objects as JSON
6. **Run SSE on a dedicated FPM pool**: see "PHP-FPM & nginx Recommendations" above
7. **Don't fight the deadline**: prefer raising `sse.maxDuration` over disabling it. Bounded streams + `EventSource` auto-reconnect is the right shape

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

### SSE connection drops every few minutes

This is by design — see "SSE Reliability Model" above. Streams cleanly
end after `sse.maxDuration` seconds (default 300) and the browser's
`EventSource` immediately reconnects. If you see noisy reconnect logs
on the client and don't want them, raise `sse.maxDuration` (and the
matching `request_terminate_timeout` / `fastcgi_read_timeout`).

### PHP-FPM saturating during SSE traffic

Symptoms: every endpoint (including `/health.php`) starts queueing,
`pm.max_children` workers all show as busy, FPM status page shows
`active processes` == `total processes` for many minutes.

Root causes and fixes:

- **SSE pool too small.** Watch your peak concurrent SSE clients with
  `redis-cli --scan --pattern 'sse:connection:*' | wc -l`. Set
  `pm.max_children` to peak × 1.2.
- **SSE not on its own FPM pool.** Move it — see the dedicated-pool
  section above. A single misbehaving stream type can otherwise starve
  unrelated routes.
- **Streams are exceeding their deadline.** Check FPM slowlog. If you
  see streams running longer than `sse.maxDuration`, something is
  bypassing the deadline (custom loop code that doesn't check, or an
  app-level `set_time_limit(0)` call after the SSE setup). The
  `request_terminate_timeout` will catch them eventually but a fix
  in the stream code is cheaper than the SIGTERM.
- **Client doesn't reconnect cleanly.** Check the browser network tab:
  `EventSource` should reconnect within ~3s of a stream ending. If
  it's spinning up reconnects every read, the heartbeat is being
  buffered — verify `X-Accel-Buffering: no` is making it through and
  that nginx has `fastcgi_buffering off`.

### SSE messages arrive in batches instead of in real time

Buffering is happening somewhere in the stack. Check, in order:

1. nginx: `fastcgi_buffering off` AND `proxy_buffering off` for the SSE location.
2. Cloudflare or other CDN in front: SSE may be buffered unless you've
   explicitly enabled streaming for the route.
3. PHP: `output_buffering = Off` and `zlib.output_compression = Off` —
   Proto sets these per-stream but a global `php.ini` value can interfere
   with the very first bytes.
4. Browser: dev tools occasionally batch SSE events visually even when
   they arrive on the wire. Check with `curl -N` to confirm.

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
