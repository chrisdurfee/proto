# Proto Realtime Server (design)

Status: proposed. First consumer: Rally.

## Problem

Today every open SSE stream is one PHP-FPM worker blocked in `\Redis::subscribe()` (`RedisServerEvents`). A worker costs tens of MB, so one box holds tens to low hundreds of live streams. Rally's production pool is `pm.max_children = 70`. One person with notifications, a message thread, and an auction room open uses three workers.

Redis pub/sub is already the fan-out bus. The limit is the process model, not Redis.

## Goal

One long-running PHP process that holds thousands of idle SSE connections and speaks the exact wire format clients already parse, with no frontend changes. The rest of Proto stays request-per-process.

Non-goals: making models, controllers, or the database layer async. Replacing FPM for normal API traffic.

## What the existing streams need (Rally inventory, 26 endpoints)

| Class | Streams | What the gateway must do |
| --- | --- | --- |
| Pass-through | notification, project list, project tasks, partner customers/invoices, convoy/event `delete` + `signal` | Forward the Redis payload (or a trivial reshape) |
| Shared hydrate | conversation messages, vehicle bids, vehicle QA, assistant messages (4), convoy/event `merge`, festival chat, support queue, client conversations, listing comments | Publisher sends `{id, action}`; PHP reads the row. Same result for every viewer, so do it **once per message** |
| Per-viewer | messaging inbox, activity presence, assistant conversation list, forum replies (`userLiked`), drive/event comments (`liked`, `canModerate`), partner inbox | Output depends on the viewer. Inboxes have one subscriber each; rooms need the viewer bits split out |

Every stream also depends on:
- a policy check before subscribing (from public, to signed in, to membership lookups);
- channel names derived from the session, route params, or a DB lookup (participant status channels);
- the control channels `sse:close:{connectionId}` and `sse:user:close:{userId}` (logout kick);
- the stale-connection singleton per session + path + `sseClient`.

A dumb Node-style fan-out could only take about 5 of the 26. The design below keeps all of the PHP logic where it already lives.

## Architecture

```
Browser ──GET /api/.../sync (cookie)──► Apache ──proxy──► proto realtime (1 process per core)
                                                              │  1. authorize (once per connect)
                                                              ├──────► POST /internal/realtime/authorize ──► FPM (normal request)
                                                              │         runs route policy + getSyncChannel()
                                                              │  2. subscribe (one Redis SUBSCRIBE per channel, shared)
                                                              ├──────► Redis
                                                              │  3. hydrate (once per message, or per viewer)
                                                              └──────► worker pool (Proto bootstrapped, blocking DB is fine)
                                                                        runs the controller's handleSyncMessage()
PHP API ──publish {id, action}──► Redis   (publishers unchanged)
```

### 1. Runtime

- **Amp v3 on Revolt.** Fiber-based, so server code reads like sequential PHP (matches Proto's existing `Fiber` use in `Http/Loop`). Packages: `amphp/http-server`, `amphp/redis`, `amphp/parallel`.
- Ships as an optional package (`protoframework/realtime`) so Proto core keeps its small dependency list. Command: `php proto realtime:serve --port=9100 --workers=4`.
- One process per CPU core behind the proxy; each holds its own connections. Redis fan-out means processes never need to talk to each other.
- The server never touches `session()`, `Config` request state, `ConnectionCache`, or any Proto static. That is the whole reason it is a separate, small program instead of Proto-in-a-loop.

### 2. Authorize (keeps policies and channel logic in PHP)

On connect the server makes one internal request to PHP with the original path, query, and cookie:

`POST /internal/realtime/authorize` (loopback or shared-secret header only)

PHP resolves the route exactly as today, runs the policy, and instead of opening a stream calls a new `SyncableTrait::syncDescriptor()`:

```json
{
  "allow": true,
  "userId": 42,
  "channels": ["conversation:88:messages", "user:17:status"],
  "hydrate": "shared",
  "handler": "Modules\\Messaging\\Controllers\\MessageController",
  "route": {"conversationId": 88},
  "streamKey": "sse:connection:42:<session>:<pathHash>:<sseClient>",
  "ttl": 1800
}
```

- `hydrate`: `none` (forward raw), `shared` (once per message), or `viewer` (per subscriber).
- DB-derived channel lists (messaging participant status) are computed here, the same way `getSyncChannel()` does now.
- Denied means `403` to the browser, as today.
- Re-authorize every `ttl` seconds. The `sse:user:close:{userId}` channel still kicks on logout immediately.

Controllers that use `SyncableTrait` get `syncDescriptor()` for free. A controller opts into a mode with one method (`getSyncHydrateMode()`, default `shared`). Controllers that call `redisEvent()` directly (Rally's partner messaging) move to the trait.

### 3. Subscribe and fan out

- One Redis subscriber connection per process. The first client on a channel issues `SUBSCRIBE`; the last one to leave issues `UNSUBSCRIBE` (reference count).
- Messages are delivered to every local client on that channel.
- Control channels are honored: per-connection close, per-user close, and the stale-connection singleton (a new stream with the same `streamKey` closes the old one).
- Wire format is unchanged: `event: message\ndata: <json>\n\n`, `: heartbeat\n\n` every 15 s.
- `maxDuration` rises from 300 s to about 30 min. The FPM limit forced 300 s; clients reconnect on their own either way.

### 4. Hydrate (reuses `handleSyncMessage`)

A pool of worker processes (`amphp/parallel`) that bootstrap Proto normally. Blocking DB and Redis calls are fine there because they never block the event loop.

- `shared`: one hydrate call per `(handler, route, message)`. Result goes to every subscriber. A bid with 5,000 watchers costs one PHP call, not 5,000.
- `viewer`: one call per `(handler, route, message, userId)`. Right for one-subscriber inboxes. For large rooms, the stream moves to `shared` once the viewer bits leave the payload (see migration).
- The worker runs the controller's existing `handleSyncMessage($channel, $message, $request)` with a synthetic `Request` (route params + viewer). `null` skips; `false` closes that subscriber.
- Per-message work is isolated: a worker crash retries once, then drops that message for that stream instead of taking down the process.
- Worker pool size is bounded, with a short queue. Under overload, `shared` hydration is coalesced (the latest message per stream wins) rather than queued forever.

### 5. Deployment (Rally on OVH)

- New `realtime` container from the same baked image, `SERVICE_ROLE=realtime`, one process per core it is given.
- **Phase 1 routing:** Apache sends `GET .../sync` to the realtime container with `mod_proxy_http` and `flushpackets=on`, replacing the `SetHandler` to the FPM `sse` pool. An idle proxied connection costs an Apache event thread (small) instead of a PHP process (large). `MaxRequestWorkers` rises accordingly.
- **Later:** move TLS termination to the edge nginx (HTTP mode) and route `/sync` there directly, which removes Apache from the stream path.
- The FPM `sse` pool stays in place as the fallback until every stream has moved, then shrinks to near zero.
- Graceful restart: on `SIGTERM`, stop accepting, send `retry: 1000` to every client, close. The swap script restarts the realtime container after web is healthy.
- Metrics at `/metrics` on an internal port: open connections, channels, messages/s, hydrate latency and queue depth. The CRM server panel reads the connection count.

## Security

- The authorize endpoint is internal only (bound to the Docker network plus a shared secret), and it only answers for routes that use `SyncableTrait`.
- The realtime server never sees the session store or DB credentials beyond what workers need, and it never decides access itself. PHP policy is the single source of truth.
- Channel names come only from the authorize response, never from the client. This also closes the unvalidated `type` in Rally's activity stream.
- Re-authorize on `ttl` so revoked access (removed from a conversation, lost a role) ends within minutes even without an explicit kick.

## Migration order (Rally)

| Phase | Streams | Work |
| --- | --- | --- |
| 1: core | notification, project list, project tasks, convoy/event signals + deletes | Server, authorize endpoint, pass-through mode, Apache routing, metrics, load test |
| 2: shared hydrate | conversation messages (main + partner), vehicle bids, vehicle QA, assistant messages ×4, festival chat, support queue, client conversations, listing comments, convoy/event merges | Worker pool + `shared` mode |
| 3: viewer | messaging inbox, assistant conversation list, partner inbox (one subscriber each: `viewer` mode). Forum replies, drive/event comments, activity presence: move `liked` / `canModerate` / follow flags out of the stream (client keeps its own, or fetches), then `shared` | Payload contract changes, frontend touch-ups |

Fix before or during migration (found in the inventory; each is also a bug today):
- Vehicle bid sync drops `auction_extended` and `auction_ended` (`VehicleBidController.php:236`).
- Client conversation sync swallows deletes (`ClientConversationController.php:175`).
- Assistant message sync null-dereferences on `dynamic` messages without a row (`AssistantMessageController.php:253`, `ScopedAssistantMessageController.php:309`).
- Activity sync builds its channel from an unvalidated query string (`ActivityController.php:174`).
- Four streams have no publisher (`assistant_user:*`, `partner:*:customers`, `partner:*:invoices`, `listing:*:comments`); either publish or remove.

## Capacity expectations (to confirm by load test)

- Idle SSE connection in Amp: a few KB of memory. One process should hold 10k+ idle streams; the ceiling is file descriptors (`ulimit -n`) and Apache threads in Phase 1.
- Hot-room cost is per message, not per viewer, for `shared` streams.
- Acceptance target on the OVH VPS-4: 10,000 concurrent streams, p99 publish-to-deliver under 250 ms for pass-through, FPM `sse` pool at zero.

## Open questions

1. Package name and whether it lives in the Proto repo or a sibling repo.
2. Worker pool size default (start at cores ÷ 2).
3. Whether Phase 1 goes straight to edge nginx HTTP mode instead of Apache proxying.
