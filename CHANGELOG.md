# Changelog

All notable changes to Proto Framework are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.41] - 2026-10-12

### Security
- **Join-free lookups keep tenant/visibility scopes** — 2.0.40's `Storage::fetchWithoutHooks()` (used by `fetchWhereWithoutJoins()`, `BatchMap` and batch enrichment) skipped `setCustomWhere()`, so scopes apps put there (tenant, visibility, soft delete) were not applied to enrichment queries. It now runs `setCustomWhere()` with no modifiers and skips only `setOrderBy()` / `setGroupBy()`.
- **CSV export uses the sort/date allow-lists** — `CsvExportTrait` passed raw `orderBy` / `dates` / `groupBy`, leaving the comparison oracle closed in 2.0.39 open on export routes.
- **`Jwt::decode()` hardening** — tokens without `exp` are rejected (pass `$requireExp = false` to allow), the header must declare `HS256`, `nbf` is honored, malformed payloads return `null` instead of a `TypeError`, and 30s of clock skew is tolerated (`Jwt::LEEWAY`). `encode()` always advertises `HS256`.
- **`Cipher` v2 format** — new ciphertexts (prefixed `v2.`) use HKDF-derived encryption/MAC keys and authenticate the IV; the MAC is checked before decrypting. Legacy values still decrypt. `Encryption::encrypt()` / `decrypt()` honor their `$key` argument, and a plaintext of `"0"` no longer decrypts to `null`.
- **Strict trusted-proxy CIDRs** — a malformed prefix (`10.0.0.0/`, `10.0.0.0/abc`) read as `/0` and trusted every address; it now matches nothing. IPv4-mapped IPv6 peers (`::ffff:a.b.c.d`) match IPv4 rules.
- **Validator rejects bad input** — non-scalar values and values the sanitizer cannot use are field errors: `"abc"` for `int` used to become `0` and pass, `"maybe"` for `bool` passed as `null`, and arrays / JSON numbers / length-limited non-string rules (`int:11`) threw a `TypeError` (500).

### Fixed
- **Sensitive-field detection matches words** — 2.0.39's substring match made `businessName`, `processName` (ssn), `keyword`, `hockeyTeam` (key), `tokenCount` and `emailVerified` unsortable. password/passwd/secret/token/apikey still match anywhere; key, salt, otp, ssn, email, phone, mobile, pwd match as whole words; names ending in verified/count/at/status/… are metadata. Columns listed in `$sortableFields` keep a compound cursor (`Model::isCursorSafeField()`) instead of falling back to an id cursor that skipped or repeated rows.
- **`dates` accepts browser formats** — ISO timestamps with `Z` or an offset (`Date.toISOString()`) are converted to the app timezone, and `datetime-local` values without seconds are accepted. They were silently dropped in 2.0.39/2.0.40.
- **Uploads match their real type** — with the declared type no longer trusted, libmagic aliases (`audio/x-wav`, `application/x-rar`, `image/x-ms-bmp`, `application/x-gzip`) and Office files reported as `application/zip` or `application/vnd.ms-office` / CDFV2 failed allow-lists. `UploadFile::canonicalMime()` normalizes them and Office containers map by extension.
- **Redis recovers** — after a failure the driver stays offline 5s, then reconnects (it stayed offline for the life of a worker, silently skipping cache invalidation). `subscribe()` / `psubscribe()` lift the read timeout while blocking, so idle subscriptions are not dropped after 1s.
- **Transactions recover** — when a savepoint is lost (the server rolled back on a deadlock) the adapter rolls back fully and resets its level (`Mysqli::abortTransaction()`, `rollbackToLevel()`). `JobQueue::work()` rolls back transactions a job handler left open. `DatabaseDriver::transactional()` nests via a savepoint instead of joining, so a failed unit undoes only its own writes.
- **Dispatch timing** — retry times are computed on the database clock (`NOW() + INTERVAL ? SECOND`; PHP/MySQL time zones may differ), and a batch stops at half the stale window and releases unsent items so they cannot be reclaimed and sent twice.
- **Query builder / filters** — `Insert::onDuplicate()` rendered invalid SQL; `AdapterProxy::set('raw clause')` rendered `0 = raw clause`; `Filter::format()` turned lower-case `like` into `=`, `['col','IS NOT',null]` into `IS NULL`, bound `BETWEEN` arrays as one value, and let malformed tuples shift later placeholders.
- **Router** — `/user/5/?tab=x` matched no route (the trailing slash was trimmed before the query string was removed).
- **Generators** — generated models declare `joins(object $builder): void`, and class templates no longer emit `class X  extends Y` / `class Xextends Y`.

### Changed
- `LoginRateLimiterMiddleware`'s per-account limit is configurable (`$accountAttemptsPerHour`, `null` disables). It counts every attempt, so a known username can be locked out for up to an hour; tune it or count failures in your auth flow.
- Realtime docs: add your CDN / load balancer ranges to `realtime.trustedProxies`, or `maxConnectionsPerIp` treats everyone behind the edge as one client.
- About 170 new unit tests (Validator, policies, query builder rendering, router matching, events, filters, transactions).

### Upgrade notes
- **Cipher v2 is one-way for rollbacks**: values encrypted by 2.0.41 cannot be decrypted by 2.0.40 or earlier, and are 3 characters longer (check exact-width columns).
- **Validator is stricter**: requests that sent non-numeric strings to `int`/`float` rules or non-boolean strings to `bool` rules now get a 422 instead of a silently coerced value.
- **`Jwt::decode()`** rejects tokens without `exp` unless `$requireExp` is false.

## [2.0.40] - 2026-10-12

### Security
- **File sessions rotate ids and set cookie flags** — `FileSession::refreshId()` never regenerated the id (the session is closed right after `init()`), so login rotation silently kept the pre-login id. Native sessions now use `session.use_strict_mode` and the framework's cookie attributes (HttpOnly; Secure + SameSite=Strict outside dev) via the new `Cookie::sessionCookieParams()`.
- **Session store outage fails closed** — `Session::init()` silently fell back to per-server, unflagged file sessions when Redis/DB was unreachable. It now throws `SessionUnavailableException` (503). Set `"sessionFallback": "file"` to restore the old fallback.
- **Client IP only from `X-Forwarded-For` by default** — `PublicIp` gave pass-through headers (`CF-Connecting-IP`, `X-Real-IP`, `Client-IP`) priority, so a request reaching a trusted hop without going through the proxy could pick its own IP and rotate past the login limit. Only `X-Forwarded-For` (right-to-left trusted walk) is read unless `trustedProxyHeaders` lists others. Trusted-proxy CIDRs now match IPv6 (Cloudflare's IPv6 ranges never matched). The realtime server appends its peer to `X-Forwarded-For` instead of passing a client's header through, and no longer forwards `X-Real-IP` / `CF-Connecting-IP`.
- **Per-account login limit** — `LoginRateLimiterMiddleware` adds a fail-closed 100/hour limit per username from any IP, on top of the per-IP limit.
- **Uploads judged by detected type** — `FileValidator` trusted the client-declared MIME type and skipped the content check with the default MIME list, so an SVG/HTML file labeled `image/png` passed. `svg`/`svgz` are no longer in the default `supportedFileTypes` and are treated as active content (renamed `.txt` without an explicit allow-list). Stored names use 128-bit random `File::randomName()` instead of guessable `uniqid()`.
- **Realtime connection caps** — `realtime.maxConnections` (20000) and `realtime.maxConnectionsPerIp` (100) are reserved before the upstream authorize, so a connect flood cannot queue without bound behind PHP.
- **Scratch scripts removed** — `src/Database/Migrations/test.php` (ran every migration when executed) and `src/Http/Test/index.php` no longer ship.

### Fixed
- **Jobs subsystem starts** — `JobQueue` instantiated the `Events` singleton with `new` (fatal on every `Jobs::dispatch()`, queue, scheduler and worker). Workers and routines catch `\Throwable`.
- **Jobs run once** — `DatabaseDriver::pop()` claims with a guarded `UPDATE … WHERE status='pending'` and checks affected rows; `releaseStaleJobs()` returns jobs stuck in `processing` past `reserve_timeout` (default 900s, reaped every `reap_interval` = 60s). Jobs whose class is missing go straight to `markFailed()` instead of killing the worker. `markFailed()` / `retryFailedJob()` join an open transaction; retries keep the job's own `max_retries` / `timeout`.
- **Scheduler dispatches** — `JobSchedulerRoutine` built an empty `Scheduler` each tick. Schedules now come from `jobs.schedule` config (`job` + `every` / `daily` / `weekly` / `at`) or an overridden `schedule(Scheduler)` hook; `Scheduler::dispatchDue()` queues each run once per cron minute. `JobCleanupRoutine` uses `processed_at` / `failed_at` (it filtered on a nonexistent column and deleted nothing). Kafka re-publishes delayed messages instead of dropping them.
- **Dispatch queue retries** — one failed email/SMS/push left the whole batch in `sending` forever. Items now settle individually: sent rows are removed, failures return to `pending` with backoff (60s doubling to 1h) and become `error` after 5 attempts, and rows stuck in `sending` over 30 minutes are reclaimed. Tunable on `QueueRoutine` (`$maxAttempts`, `$retryDelaySeconds`, `$maxRetryDelaySeconds`, `$staleMinutes`).
- **Dispatch correctness** — `WebPush::send()` reported success as error; queued SMS ignored its stored body; a per-message SMS `customDriver` replaced the shared driver for later messages; dispatch controllers call hooks via `static::` so app overrides apply; the PHPMailer SMTP timeout is bounded (`email.smtp.timeout`, default 15s, was 300s).
- **Nested transactions use savepoints** — a second `beginTransaction()` issued `START TRANSACTION`, which made MySQL commit the open one (including a test's isolation transaction). Nested calls use `SAVEPOINT` / `RELEASE` / `ROLLBACK TO`. New `Mysqli::inTransaction()` / `getTransactionLevel()`.
- **No `die`/`exit` in library code** — router request validation, resource 404s, CSRF/domain middleware and `ApiRouter::error()` throw `HttpTerminationException` (same status and body); `JwtService` throws `IntegrationException`. They killed workers and the realtime bridge. `Process::__destruct()` called `die()`, ending the PHP process (exit code 0) whenever a routine object was destroyed.
- **Session cleanup routine** — `CleanUpSessionRoutine` called instance methods statically (fatal); it now deletes expired and empty sessions in bounded batches.
- **`fetchWhereWithoutJoins()` returns rows** — it ran the storage's `setOrderBy()` / `setCustomWhere()` hooks, which apps write against joined aliases (e.g. `ConversationParticipant`'s `ORDER BY c.last_message_at`), so the join-free query failed and returned nothing. New `Storage::fetchWithoutHooks()`.
- **`find()` / `findAll()` callbacks bind by reference** — `Storage::callBack()` passed `$params` by value, so values a `function ($sql, array &$params)` callback appended were lost.
- **Realtime stays up** — an exception in a stream's periodic reauthorize (e.g. Redis subscribe failing) escaped the event loop and killed the process; it now closes that stream with a reconnect hint, and the loop logs uncaught errors.
- **Redis session writes merge** — `__set` / `__unset` re-read the stored session and apply only the changed key, so parallel requests no longer erase each other's writes (e.g. CSRF rotation vs login).
- **Owned cache locks** — stampede locks carry an owner token and are released with an atomic compare-and-delete (`Cache::deleteIfEquals()`).
- **Typos fail loudly** — unknown methods on models, storage proxies and the query builder did nothing visible: `Model::fetchWher()` returned `null` and `->wher(...)` silently dropped the clause. They now throw `BadMethodCallException`. `Model` documents forwarded storage methods with `@method` tags.
- **`ItemRequest`** imported `Proto\API\Validator` (wrong case; fatal on Linux).

### Changed
- **`ThrottleMiddleware` rate-limits instead of sleeping** — it slept 1s per request (pinning a worker, limiting no one); it now applies a non-blocking per-caller, per-route limit (`$requestsPerMinute`, default 60) with 429 + `Retry-After`.
- **Storage events only built for listeners** — `StorageProxy` re-converted every result to publish events nobody subscribed to, doubling row conversion cost on list reads. New `Events::listening()`.
- `Rest\Request` gains fluent `connectTimeout()` / `timeout()`.
- **Tooling** — PHPStan 2 with `phpstan.neon` (level 1 + baseline) so `composer analyze` works; GitHub Actions CI (PHP 8.4, MySQL 8, Redis 7) runs `composer validate`, PHPUnit and PHPStan; `phpunit.xml` drops Laravel leftovers; unused `web-token/jwt-library` pin removed; branch alias `2.0.x-dev`.
- **Docs** — the AI agent guide (`.github/copilot-instructions.md`) is corrected against the code (policy signatures, gates, storage access, factories, PHP 8.4); root `copilot.md` is replaced by `AGENTS.md` / `CLAUDE.md` pointers; README fixes and a new `CONTRIBUTING.md`; old audit reports move to `docs/history/` with a `docs/README.md` index.

### Upgrade notes
- **Run migrations**: `AddRetryColumnsToDispatchQueues` adds `attempts` and `next_attempt_at` to `email_queue`, `sms_queue` and `web_push_queue`. Queue routines fail without them.
- **`JobQueue::work()` / `Jobs::work()` return when no job is available** (and now return the number processed). Long-running workers must pass `stop_when_empty => false` (optionally `max_idle`, `max_time`). The stored `attempts` now counts runs started.
- **Sessions fail closed** when the configured store is down (503) unless `"sessionFallback": "file"`.
- **Client IP**: if you relied on `CF-Connecting-IP` / `X-Real-IP` without `X-Forwarded-For`, set `"trustedProxyHeaders": ["CF-Connecting-IP", "X-Forwarded-For"]`. Cloudflare → proxy chains that append `X-Forwarded-For` need no change as long as both ranges are in `trustedProxies`.
- **SVG uploads** need an explicit allow-list (`supportedFileTypes` / `$allowedExtensions`) and should be served from a separate origin.
- **Unknown model/storage/query-builder methods now throw** `BadMethodCallException` instead of returning `null`; run your test suite to catch code that relied on that.

## [2.0.39] - 2026-10-12

### Security
- **`lastCursor` no longer leaks secret columns** — the compound cursor was built from raw rows (before `$fieldsBlacklist` is stripped), so `?orderBy={"password":"asc"}` returned the password hash (or any token/secret column) of the last row in `lastCursor`, and walking `offset` dumped every row. A sort on a sensitive column now falls back to the plain id cursor.
- **Client sort/date/group columns are allow-listed** — `orderBy`, `groupBy` and `dates.field` only ran identifier sanitizing, so a client could sort or `BETWEEN`-filter on `reset_token`, `password`, etc. and recover values by binary search (a comparison oracle, also reachable through a forged compound cursor). `ApiController::getAllInputs()` now drops keys outside `requestSortColumns()`; `ResourceController` supplies the new `Model::sortableFields()` (fields minus blacklisted, secret and PII columns — audit timestamps and `id` stay sortable; override with `protected static ?array $sortableFields`). `dates.start` / `dates.end` must be plain dates or datetimes.
- **`whereBetween()` / `whereJson()` bind their values** — the raw query builder spliced both into the SQL string. Through the table builder they are now bound; the raw `Query` methods reject non-numeric inline operands (pass `?` and bind) and validate JSON paths.

### Fixed
- **Uncaught errors return JSON** — `Router::activateRoute()` only caught `HttpTerminationException`, so any other exception produced a blank 500 (tracking off, and `error_reporting(0)` hid it from the PHP log) or a **200 with an empty body** (tracking on). It now renders `{"success":false,"message":…}` with 500, or 503 + `Retry-After` when the database is unreachable. Messages, SQL, paths and traces only appear in dev. The exception and fatal-shutdown handlers are installed even when `errorTracking` is off.
- **Database outage is a typed error** — `AdapterProxy::fetch()` returned the adapter's `null` from an `array` method (a `TypeError` on every read when MySQL was down). It throws `Proto\Database\DatabaseUnavailableException` instead, rendered as a 503.
- **Error tracker no longer echoes its payload** — when writing the error-log row failed, the 500 body contained the raw message, file, trace, URL and client IP. That now goes to the server log; the client gets a generic message outside dev.
- **Production keeps logging** — non-dev `Error::enable(false)` reports `E_ALL` minus deprecations with `display_errors=0`, `log_errors=1` (was `error_reporting(0)`), and `errorHandler` honors `error_reporting()` / `@`.
- **Redis read timeout** — `RedisDriver` sets `OPT_READ_TIMEOUT` (`cache.connection.readTimeout`, default 1s) and marks itself offline after a `RedisException`, so a hung Redis costs one timeout per request instead of up to 60s per call.
- **INCR failure no longer bypasses fail-closed limits** — `incr()` returns 0 when Redis drops mid-request, which `RateLimiter` read as "zero requests". It is now treated as an outage, so `failClosed()` limits (login, reset, OTP) use the fallback counter.
- **Paging is clamped** — `limit` is kept in `[1, maxLimit]` and `offset` ≥ 0 (a negative limit produced a SQL error); `lastCursor` / `since` must be scalars; a client `groupBy` no longer throws a `TypeError`.
- **Lazy connect for transactions** — `Mysqli::beginTransaction()` returned false (and the following writes ran in autocommit) when it was the first statement of a request.

### Upgrade notes
- A client `orderBy` / `dates.field` naming a column outside `sortableFields()` is now ignored. Models that need to sort on such a column (rare) can list it in `$sortableFields`.
- Code calling the raw `Query::whereBetween()` with string values, or `Query::whereJson()` with a value, must pass `?` and bind (or use the table builder).

## [2.0.38] - 2026-10-11

### Security
- **Empty CORS allow-list fails closed outside local/dev/testing** — `Headers::isAllowedOrigin()` previously treated an empty `cors.allowedOrigins` as "allow every Origin" in every environment, so a missing prod config reflected credentials-capable CORS for any site. Prod and staging now refuse every Origin until an allow-list is set; local/dev/testing keep the open fallback.
- **Request secrets redacted from error logs** — `Error` stored `Request::all()` (and the request URL) on every logged error/exception, so a failing login or form write persisted passwords and tokens in `proto_error_log.query` / `url`. Sensitive keys are replaced with `[REDACTED]` before encode, and URL user-info / query values matching those keys are scrubbed too.

### Fixed
- **cURL timeouts by default** — `Proto\Http\Rest\Curl` had no `CURLOPT_CONNECTTIMEOUT` / `CURLOPT_TIMEOUT`, so a hung peer could pin an FPM worker until PHP's default socket timeout. Defaults are now 10s connect / 30s total, overridable via `connectTimeout()` / `timeout()`.

## [2.0.37] - 2026-10-11

### Changed
- **Changelog catch-up** — filed the 2.0.35 cache notes and 2.0.36 query-id binding security note under their shipped version headings.

## [2.0.36] - 2026-10-11

### Security
- **Query-id binding on write routes without `:id`** — `bindRouteId()` previously only enforced a match against a path `:id`, so routes like `PATCH /api/resource?id=<owned>` still authorized the query id while `update()`/`setup()`/`merge()` wrote the body `id`. A client could pass an owned row in `?id=` and overwrite any other row via the body. Writes now bind to `getResourceId()` (route `:id`, else request `id`): a mismatched body `id` returns `400`, and a missing one is filled from the authorized id.

## [2.0.35] - 2026-10-03

### Changed
- **Writes no longer SCAN the cache** — every resource write ran several `SCAN MATCH` passes over the whole Redis keyspace (generic GET keys, then `get:{id}` and `get:{id}:*` for each id/slug/guid/uuid, before and after the write). Custom GET keys now carry the list generation, and `get()` keys carry a per-row generation (`Class:getgen:{identity}`), so a write is one `INCR` per token. Orphaned keys age out with their TTL. `deleteGenericMethodCaches()` stays for subclasses that want an eager sweep but is no longer called by `deleteAll()`.
- **Generation tokens start at the clock** — a missing token is seeded with the current time in milliseconds (`SET NX`, 7-day TTL refreshed on each bump) instead of restarting at 1. After an eviction or expiry, the old counter restarting low could make keys cached under an earlier generation reachable again. Applies to the list generation too.
- **No per-session keys for cookieless guests** — a guest whose request has no session cookie gets a new session id every time, so their `get()`, non-shared `all()` and custom GET keys could never be read again; bots and link previews only filled the cache and pushed live keys (and any rate-limit counters sharing the instance) out. Those requests now skip the cache. Shared keys are unaffected.

### Upgrade notes
- `get()` and custom GET cache keys change shape, so existing entries miss once after deploy.

## [2.0.33] - 2026-10-02

### Security
- **Shared streams split by access** — a `shared` stream's result is computed with one viewer's session and sent to every viewer of the same URL. Viewers are now grouped by URL *and* their authorized channel list, so two viewers PHP authorized differently never receive each other's result.

### Added
- **Upstream concurrency limit** — at most `realtime.upstreamConcurrency` (default 32) authorize and hydrate calls run against PHP at once; the rest wait. Hydrate calls beyond `realtime.upstreamMaxQueued` (default 2048) waiting are dropped and counted as `hydrateShed` instead of piling onto PHP-FPM. New metrics: `upstreamInFlight`, `upstreamQueued`, `upstreamQueuedPeak`, `hydrateShed`. `amphp/sync` is now a direct dependency.

### Changed
- **Reconnects are spread out** — streams closed with a "come back soon" hint (drain on deploy, max duration, revoked access, slow client) now tell the browser to retry after a random 1–5s instead of exactly 1s. The max-duration cut is spread over 90–100% of `maxDurationSeconds`, and each stream's first re-authorize over 50–100% of `reauthorizeSeconds`, so streams that opened together no longer expire or re-check together.

## [2.0.31] - 2026-10-02

### Security
- **Active-content upload names** — without an `$allowedExtensions` list, `File::createNewName()` (and so `UploadFile`) no longer keeps executable or page-rendering extensions (`php*`, `phtml`, `html`, `xhtml`, `xml`, `xsl`, `js`, `cgi`, `sh`, …); they are stored as `.txt`. An image/HTML polyglot named `x.html` could otherwise pass a MIME check and be served as a page (stored XSS). New `File::isActiveContentExtension()`. Extensions are also reduced to `[a-zA-Z0-9]`.
- **PUT runs the update hook on existing rows** — `setup()` (resource PUT) is authorized like `update()` and overwrites an existing row, but it ran `modifyAddItem()`, so every field restriction an app placed in `modifyUpdateItem()` (and the base immutable-field strip there) could be skipped by sending PUT instead of PATCH. `setup()` and `merge()` now run `modifyUpdateItem()` when the id exists and `modifyAddItem()` otherwise (new `modifyUpsertItem()` / `upsertTargetExists()`).

### Fixed
- **Route-parent binding only uses model fields** — `$routeParams` entries that are not model fields (a param mapped to a differently named column, e.g. `:messageId` → `ticket_id`) are skipped by the 2.0.30 route-parent binding instead of producing an invalid filter.

## [2.0.30] - 2026-10-01

### Security
- **Route id binding** — `ApiController::getResourceId()`, the base `Policy::getResourceId()` and `ModelPolicy` now resolve the route `:id` first; the request `id` is only a fallback for routes without `:id`. Previously the controller read `?id=` / form `id` first while policies checked the route id, so a client could pass its own row in the route and read, update or delete any other row. `update()`, `setup()` and `merge()` reject a body `id` that differs from the route id with `400` and fill a missing one from the route.
- **Nested rows bound to their route parent** — controllers that declare `$routeParams` now require a child row to belong to the parents in the URL: `get()` adds the parent values to the lookup; `update`/`updateStatus`/`delete`/`merge` return `404` for a row under another parent; `setup` may create a new id but not take over another parent's row. Writes pin route parent values so the body cannot move a row.
- **`Model::$guarded`** — fields a client may never write. Stripped from client input on add/update/setup/merge before `modifyAddItem()`/`modifyUpdateItem()`, so server code can still set them. `immutableFields` only ever protected updates.
- **`DomainMiddleware` checks the caller** — it compared the `Host` header (always the API host) against the allow-list, so a foreign `Origin` was never blocked. It now checks the `Origin` host, or the `Referer` host when there is no `Origin`, against the allow-list plus the API's own host. `Origin: null` is refused; requests with neither header still pass.
- **Affected rows** — `Adapter::getAffectedRows()` / `executeAffected()` and `TableStorage::executeAffected()`. `execute()` returns `true` whenever a statement runs, so guarded writes (`UPDATE … WHERE balance >= ?`) must check affected rows to detect a failed guard.
- **Cache keys and scope** — generic GET and `get()` cache keys include every route param, not only `:id`. Custom GET methods on `$cacheSharedPayload` controllers are cached per user unless listed in the new `$sharedCacheMethods`. Error responses are never cached. Custom non-GET methods invalidate, writes invalidate before and after the controller call, and `setup`/`merge` drop the row's `get()` keys. `ModelPolicy::invalidateFor($controllerClass, $id)` lets services and jobs invalidate after writes that bypass the controller.

### Changed (upgrade notes)
- A write whose body `id` differs from the route `:id` now fails with `400` instead of acting on the body id.
- With `$routeParams` declared, a child id from another parent now returns `null` on get and `404` on writes.
- Custom GET methods on shared-cache controllers are no longer shared by default; list viewer-independent ones in `$sharedCacheMethods` to keep sharing them.
- `DomainMiddleware` now blocks cross-site requests identified only by `Referer`; browsers must call protected routes from an allowed origin.

### Added
- **Readable `XSRF-TOKEN` cookie for SPA CSRF** — `CrossSiteRequestForgeryGate::setToken()` / `rotate()` now mirror the session CSRF token into a non-HttpOnly `XSRF-TOKEN` cookie (same domain / Secure / SameSite rules as other cookies; expiry follows `sessionLifetime`). Clients read the cookie and send it back as `X-XSRF-TOKEN` (legacy `csrf-token` header still accepted). Validation remains session-bound: the gate never trusts cookie-equals-header alone. `Cookie` gains an optional `$httpOnly` constructor flag and `Cookie::remove($name, $httpOnly)` so the readable cookie can be cleared with matching attributes. CORS `Access-Control-Allow-Headers` includes `X-XSRF-TOKEN`.
- **Optional parent-domain cookies** — set `cookieDomainWide` to `true` and, in production only, `Cookie` sets `Domain=.{domain.production}` when the request host is that parent or a subdomain. `Secure`, `HttpOnly`, and `SameSite` are unchanged. Localhost, IP hosts, and non-production envs stay host-only. Setting or clearing the cookie also clears the host-only copy of the same name so it cannot shadow the shared session.
- **Stored validators on cache hits (no viewer flags)** — `remember()` stores `$key:etag` next to the payload (one `getMultiple` on read). When `If-None-Match` matches and the controller has no viewer flags, the request throws `NotModifiedException` and the router sends a bodyless 304 without re-running the controller or re-encoding JSON. Controllers that declare `$currentUserFlags` / flag enrichments skip this path so one tag cannot cover two viewers.
- **Cache stampede lock on ModelPolicy** — `get()`, `all()`, and generic cached GET methods go through `remember()`. A miss takes `Cache::add($key . ':lock')` (SET NX EX 15s). The winner computes and stores; losers re-read instead of replaying the same SQL. If the winner never writes, losers compute themselves rather than wait forever. `ControllerHelper` no longer hard-disables wrapping in `dev`; `Cache::isSupported()` / `cache.enabled` is the only gate.
- **Dedicated Redis for sessions** — `RedisDriver` accepts an optional connection object. `RedisSession` uses `sessionConnection` when set (host/port/password) so cache LRU cannot evict `session:` keys. Unset, it still uses the application cache driver.
- **RFC 8058 List-Unsubscribe on SMTP mail** — `Email::applyUnsubscribeHeaders()` writes `List-Unsubscribe` and `List-Unsubscribe-Post: List-Unsubscribe=One-Click` onto the PHPMailer instance before send. The pairs already existed on the unused raw-header path (`setupHeader()`); the SMTP path never called it, so Gmail/Yahoo never showed one-click Unsubscribe.
  - `$settings->emailCategory` (`transactional` default, `marketing`, `digest`, `internal`) gates both the header URL and the footer `unsubscribeUrl` injected into template data. Only `marketing` and `digest` generate a token. An explicit `$settings->unsubscribeUrl` still wins for tests and callers that already built one.
  - `Proto\Dispatch\Email\Unsubscribe\EmailCategory` owns the classification so dispatch and enqueue stay in sync.
- **Conditional requests (ETag / 304) on JSON responses** — `Router\Response::json()` now fingerprints the encoded body with `Proto\Http\Router\EntityTag` and answers a matching `If-None-Match` with a bodyless `304 Not Modified`. A repeat request for unchanged data costs one round trip and a few bytes instead of the whole payload, which is the dominant cost for mobile and geographically distant clients. Validators are only attached to a representation that is actually reusable: `200` responses to `GET`/`HEAD` whose directive permits storage. Mutations, error bodies, and `no-store` responses are always sent in full.
  - Tag comparison is weak per RFC 9110 and normalizes the content-coding suffix a compressing intermediary appends (Apache `mod_deflate`/`mod_brotli` rewrite an origin tag as `"hash-gzip"` and the client echoes that value back). Without this, every browser revalidation behind Apache would miss and return a full `200`.
  - `ETag` is added to `Access-Control-Expose-Headers`, and `If-None-Match` to `Access-Control-Allow-Headers`.
- **`Proto\Http\Router\CacheDirective`** — builds `Cache-Control` and reports whether a response may be reused without revalidation. Named constructors: `noStore()`, `revalidate()`, `privateFor($maxAge, $staleWhileRevalidate)`, `publicFor($maxAge, $sharedMaxAge, $staleWhileRevalidate)`. Set per response with `Response::cache(...)` / `Headers::cache(...)`; the directive is reset by `Headers::set()` at the start of every request so a choice made by one request cannot leak into the next on a long-lived FPM worker.
- `Headers::reset()` clears memoized header state between requests and tests.
- **Batched and atomic cache primitives (toward 2.0.18)** — `Cache::getMultiple()`, `Cache::deleteMultiple()`, and `Cache::add()`, with matching `Driver` methods. `RedisDriver` implements them as `MGET`, a single variadic `DEL`, and `SET … NX EX`; the base `Driver` provides portable per-key fallbacks so existing drivers keep working unchanged.
  - `getMultiple()` returns `null` for absent keys and preserves a cached empty string as a hit, so a caller can tell "absent" from "cached empty" without a second lookup. Keys are de-duplicated and mapped back by name, since `MGET` replies positionally and a miss in the middle would otherwise shift every later value onto the wrong key.
  - `add()` is the primitive for a stampede lock, so the winner must be decided by the server. `RedisDriver` sets the value and TTL in one `SET … NX EX` (a follow-up `EXPIRE` can fail after the `SET` succeeded and leave a lock that never releases). The base-class fallback is check-then-set and therefore racy: a driver that cannot do this atomically must not be used for locking.
  - A cache outage reads as "nothing cached" (`getMultiple()` → all null) and as "lock not acquired" (`add()` → false), so an outage never fabricates a hit and never hands out a lock nobody can release.
- `Policy::deleteKeys(array $keys)` — batches cache invalidation into one round trip. Invalidation runs on the write path, so deleting one key per round trip added latency to every write in proportion to how much had been cached. `Policy::deleteKeysMatching()` and `ModelPolicy::deleteGenericMethodCaches()` now use it, so the targeted `get()` invalidation on every update/delete and the generic-method sweep each cost one `DEL` instead of one per matched key.
- **`cache.enabled` config override (toward 2.0.18)** — `Cache::isEnabled()` now honors an explicit `cache.enabled` setting in either direction. The default is unchanged (off when `env` is `dev`, on elsewhere), but that default meant the cache path was never exercised in development, so a keying or invalidation bug could only surface in an environment where it is expensive to debug. Set `cache.enabled` to `true` to exercise the real path locally, or `false` to disable caching outside `dev`.

### Changed
- **Default `Cache-Control` is now `private, no-cache, max-age=0, must-revalidate` instead of `no-store, no-cache, must-revalidate, max-age=0`.** `no-store` forbids the client from keeping the copy that a `304` refers to, so it silently disabled all revalidation. `no-cache` still requires the client to revalidate before *every* reuse, so responses are exactly as fresh as before and a browser cannot hand one user's cached response to the next user of the same device; the only change is that an unchanged payload can now be confirmed instead of retransmitted.
  - Set `router.conditionalRequests` to `false` to restore the previous `no-store` default and disable ETags globally.
  - Endpoints that must not be written to disk at all (credentials, tokens, sensitive PII) should opt out per response with `Response::cache(CacheDirective::noStore())`.
- **`Vary` is now sent** as `Origin, Accept-Encoding` — required for correctness because `Access-Control-Allow-Origin` is reflected from the request. `Cookie` is appended when the active directive allows reuse without revalidation, so a session change cannot reuse the previous user's copy within a freshness window.
- `304` responses omit `Content-Type` and any body.
- **Apps fronted by Apache/nginx must ensure the API path is exempt from `mod_expires` (or the nginx equivalent).** A server-level `ExpiresByType application/json` or `ExpiresDefault` rule emits a *second*, conflicting `Cache-Control` on API responses, which defeats revalidation and can let a cache reuse a user-specific payload.

### Fixed (toward 2.0.20)
- **Every pattern-based cache invalidation silently matched nothing (HIGH).** Cache keys are prefixed with a namespaced class name, and a backslash is the escape character in the glob syntax Redis `SCAN MATCH` uses. `Modules\Foo\Controllers\BarController:*` was therefore interpreted as the literal `ModulesFooControllersBarController:*` and matched no key, so `Policy::deleteKeysMatching()` and `ModelPolicy::deleteGenericMethodCaches()` both reported success while deleting nothing.
  - The visible consequence was stale reads after a write: `ModelPolicy::invalidateGetKeys()` is what drops the cached `get()` response for an updated or deleted row, so that row kept serving its pre-write payload until the entry expired on its own (30 minutes at the default `get` expiration). Generic (non-CRUD) method caches were likewise never swept.
  - Literal segments of a pattern are now escaped via `Policy::escapePattern()`. Parameters are escaped too: a slug or guid is client-supplied, and an unescaped `*` or `[` there would have widened the pattern and deleted cache entries belonging to other records. The intentional scope wildcard is preserved.
  - Verified against live Redis: before the fix, `get:5` and `get:5:inc=…` both survived `invalidateGetKeys()`; after it, both are removed while `get:50` is untouched.

### Fixed (toward 2.0.18)
- **`RedisSession` expired on absolute age instead of inactivity.** The TTL was only rewritten by `saveData()`, which runs on a session *write*. Most authenticated requests only read the session, so a continuously active user was signed out `sessionLifetime` seconds after signing in no matter how recently they used the app. `loadData()` now extends the TTL on every successful load, so the lifetime measures inactivity, matching how a database-backed session behaves. Verified against a live Redis: a read-only request on a session with 99s remaining restored it to the full lifetime, where the previous behavior left it at 99s.
- **List cache invalidation was defeated by the sweep deleting its own generation token (HIGH).** `ModelPolicy::deleteAll()` bumps a generation counter so every cached `all()` key changes without a SCAN, then calls `deleteGenericMethodCaches()` to drop non-CRUD method caches. That sweep classified a key by `explode(':', $key)[2]`, which assumes the `Class:scope:method:params` shape of a cached response. The generation key is `Class:all:gen`, so element `[2]` was `gen` — not a recognized CRUD method — and the counter was deleted on **every write**, immediately undoing the `INCR` performed a moment earlier.
  - Consequences: `INCR` restarted from `1` after each write instead of advancing, and a worker that had not memoized the counter in-process resolved the generation to `0`. Both made `all()` keys cached *before* a write reachable *after* it, so list endpoints could serve stale rows until the entries expired on their own. Workers also disagreed on the current generation, so whether a stale list was served depended on which worker answered.
  - The sweep now skips the generation key explicitly and only considers keys with the full response shape (at least four segments), so bookkeeping keys can never be mistaken for cached responses. Deletes are additionally batched through `Policy::deleteKeys()`.

### Fixed (post-2.0.16)
- **Early-terminating and non-router responses ignored the active cache directive.** `HttpTerminationException::respond()` (policy denials, `setError()` validation failures, rate-limit 429s) and `Proto\Http\Response::send()` (`ApiRouter::error()` 404s) sent their own headers without calling `Headers::sendCacheHeaders()`, so a route that opted into `CacheDirective::noStore()` via middleware silently fell back to whatever default was emitted while the router was booting. Both now emit the active directive. This matters most for validation failures, whose body can echo submitted input.

### Security
- **`PublicIp::fetchPublicIp()` (HIGH)** — `X-Forwarded-For` (and every other proxy header) is now resolved with the standard trusted-proxy algorithm: the value is walked **right-to-left**, skipping any entry that itself matches a configured trusted proxy, and the first non-trusted entry is used as the client IP. Previously the **first** (leftmost) entry was trusted verbatim, so with a single trusted-proxy hop (the normal reverse-proxy topology) a client could prepend an arbitrary spoofed IP (`X-Forwarded-For: 1.2.3.4, <real ip>`) and have it accepted as their identity, completely bypassing `RateLimiterIdentity::resolve()` / `Limit::failClosed()` IP-based brute-force protection on login/OTP/password-reset. If every entry in the header matches a trusted proxy (should not normally happen), this now fails safe by logging a warning and falling back to the leftmost entry rather than trusting whichever garbage produced that state. **Action required for apps behind a reverse proxy/load balancer/CDN**: `trustedProxies` must list every proxy hop between the internet and your app (see `docs/SECURITY_UPGRADE_GUIDE.md`) — this was already required for `X-Forwarded-For` to be consulted at all, but is now also what makes the spoofing fix actually effective.
- **`Curl::denyPrivateNetworks()` SSRF guard (HIGH)** — closed two bypasses of the opt-in guard: (1) `CURLOPT_FOLLOWLOCATION` is now disabled whenever the guard is enabled; redirects are followed manually via a new `requestWithRedirectGuard()` loop that re-validates every `Location` header (via `isPrivateNetworkUrl()`) before following it, capped at 5 hops — previously a validated public URL could 302 to `http://169.254.169.254/...` (or any private target) and cURL would follow it with zero re-validation; (2) the exact IP validated by `isPrivateNetworkUrl()` is now pinned for the actual connection via `CURLOPT_RESOLVE`, closing a DNS-rebinding TOCTOU gap where cURL's own independent re-resolution at connect time could return a different (private) answer than the one just approved. No API changes; still fully opt-in and BC by default.

### Fixed
- **`Mysqli` SHOW/DESCRIBE binds (toward 2.0.15)** — MariaDB/MySQL reject prepared `SHOW TABLES LIKE ?` / `SHOW COLUMNS ... LIKE ?` (syntax error near `?`). `MysqliQueryHelper::resolveNonPreparableSql()` now inlines escaped literals for `SHOW` / `DESCRIBE` / `DESC` before `prepare()`, and leaves the original SQL alone when placeholders are present but params are missing/short so the failure stays visible. Prefer the new `Adapter::tableExists()` / `Adapter::columnExists()` helpers, which use `information_schema` with real binds.
- `Data::set()` no longer uses null as an array key (PHP 8.5 deprecation). Bare `set(null)` (e.g. `Model::init()` with no data) is a no-op; key/value calls coerce a null key to `''`. `ReadOnlyArray` offset access coerces null offsets the same way.
- `Filter::format()` rejects the ambiguous `[column, arrayOfValues]` two-tuple shape (e.g. `["status", [1, 2, 3]]`) with a clear `InvalidArgumentException` instead of silently producing malformed SQL (just the column name, no operator/placeholder) plus orphaned bind params. It was indistinguishable from the legitimate raw `[sql, params]` fragment shape used by `Filter::exists()` / `Filter::notExists()` / `Filter::since()`; the two are now disambiguated by whether the first element looks like a bare column name (`isSafeColumn()`) or a SQL expression. Callers must use the explicit, unambiguous 3-tuple form instead: `["status", "IN", [1, 2, 3]]`.
- `Model::getWithoutJoins()` / `fetchWhereWithoutJoins()` / `count()` now reset `static::$skipJoins` in a `finally` block instead of a bare assignment after model construction. Previously, an exception thrown while constructing the model instance (e.g. a misconfigured join callback) left `$skipJoins` stuck `true` for the rest of the PHP-FPM worker's lifetime, silently disabling eager joins for every subsequent request on that worker (the property is declared once on the base `Model` class and is not redeclared per subclass, so this affected every model, not just the one that threw).
- `Filter::fieldLookup()` skips non-string `$fields` entries and uses the alias from computed `[['SQL'], 'alias']` tuples so `Model::qualifyFilter()` / `Filter::qualify()` no longer emit "Array to string conversion" when a model declares X()/Y()/IF() columns.
- Router snapshots middleware at registration. Deferred `api.php` flush no longer applies `router()->middleware()` added later in the same file (e.g. `ThrottleMiddleware` leaking onto `GET csrf-token`).

### Documentation
- `PolicyProxy::checkPolicyBefore()` / `checkPolicy()` docblocks now explicitly note that a `false`/absent `before()` result does not deny the request — it falls through to the per-action policy method, which can still independently allow the action. Behavior is unchanged; documentation only.

## [2.0.5] - 2026-08-16

### Fixed
- `Filter::aliased()` / `Filter::condition()` skip columns that already contain a dot so `$qualifyFilters` plus a second alias pass cannot produce `ga.ga.status` / `ps.ps.partner_id`.
- `Model::qualifyFilter()` prefixes unqualified model fields (`id`, `status`, …) on `getRows()` / `fetchWhere()` / `getBy()` / `count()` / `fetchWhereWithoutJoins()` so `WHERE id = ?` is not ambiguous when the model joins `users` (or any table with `id`).
- `Storage::count()` never returns null. A failed or empty `first()` becomes `{count: 0}` and `count` is always an int. Callers must not assume null.

## [2.0.4] - 2026-08-15

### Fixed
- `ResourceController::firstScoped()` always alias-qualifies lookup keys (`id`, slug, uuid) so `get()` is not `WHERE id = ?` ambiguous when the model joins `users` (or any table with `id`). Runs even when `$qualifyFilters` is false.
- `BatchEnrichmentTrait` / `BatchMap` no longer TypeError on missing related-row properties (`stdClass::$groupId`). Missing keys are skipped; camelCase and snake_case are both accepted.
- `Model::fetchWhereWithoutJoins()` runs `convertRows()` so join-free batch fetches expose camelCase FKs (`groupId`, `itemId`) instead of raw `group_id`.

## [1.3.53] - 2026-08-14

### Notes
- Packagist / SemVer publish for the complete 1.3.50 + 1.3.51 surface (image pipeline, policy helpers, APNs, CSV export, `rawOrderBy`, typed field formatting, realtime publish, split votes / pivot counters).
- The git tag `1.3.52` incorrectly pointed at the 1.3.51 commit. Do not publish `1.3.52`; use **1.3.53** as the clean release.
- Packagist versions come from git tags only; do not set a `"version"` field in `composer.json`.

## [1.3.51] - 2026-08-14

### Added
- `Proto\Dispatch\Apns\Apns` + `ApnsJwt` and `Controllers\ApnsController` for APNs HTTP/2 (token auth, dead-token reporting). `Dispatcher::apns()` / `Enqueuer::apns()` (enqueue prepares payload only; no framework `apns_queue` table).
- `Proto\Utils\CsvExport` and `Controllers\Traits\CsvExportTrait` (`exportMaxRows`, `fetchExportRows()`, `streamMappedCsv()`).
- `Proto\Storage\Traits\RawOrderBySupport` for injection-safe server-side `rawOrderBy` modifiers.
- `Proto\Models\Traits\FormatsTypedFields` for post-join bool/int casting via `FORMAT_*_FIELDS` constants.
- `Proto\Services\Traits\RealtimePublishTrait` (`publishRealtime()` adds the `redis:` Events prefix).
- Docs: `docs/RFC_NESTED_API_COMPOSITION.md` (deferred nested `api.php` composition / safer `resource()`; design only).

## [1.3.50] - 2026-08-14

### Added
- `Proto\Media\ImageProcessor`, `DiskScratch`, and `ImagePresets` for Imagick-based upload optimization (megapixel caps, EXIF strip, variant generation). Optional `onRemoteWrite` callback for CDN/object-header hooks.
- `ImageOptimizationTrait` on `ResourceController` (`handleOptimizedImageUpload()`) and `Proto\Media\Traits\MediaImageOptimizationTrait` for media-table services.
- `Proto\Geo\BoundingBox` and MBR prefilter support in `LocationFilterTrait` (index-friendly proximity; disable with `'mbr' => false`).
- Policy helpers: `hasRole()`, `hasPermission()`, `isOwnerOrAdmin()`, `isPublicOrOwner()`, and base `setup()` that delegates to `update()`/`default()`.
- `CurrentUserFlagsTrait` for declarative liked/bookmarked/favorited enrichment.
- `RateLimiterIdentity` plus `RateLimiterMiddleware::getIdentity()` (`user:{id}` else `ip:…`).
- `TogglePivotTrait::togglePivotWithCounter()` and `SplitVoteableTrait` for dual up/down counters.
- Configurable `Proto\Validation\PasswordPolicy`.

### Changed
- `BatchEnrichmentTrait` uses `fetchWhereWithoutJoins()` when available so unqualified FK filters are not ambiguous against eager joins.
- `LocationFilterTrait::buildProximityCondition()` now returns an **array of clauses** (MBR + distance) instead of a single clause. `filterByProximity()` iterates the result; callers that consumed the return shape directly must update.

### Breaking
- `Create::__call()` throws `BadMethodCallException` on unknown column-type methods (e.g. `$table->raw(...)`) instead of silently dropping the field. Use typed CreateField methods (`point()`, `varchar()`, etc.).

## [1.3.49] - 2026-08-13

### Added
- `ApiRouter::initialize()` auto-registers a shutdown handler that resets `Request`, `PublicIp`, `Session`, and `Gate` session cache between PHP-FPM requests (apps no longer need a manual `register_shutdown_function`).
- `Policy::isSignedIn()` and `Policy::getRouteParam()` generic helpers on the base policy class.
- `FileValidator` / `ImageValidator` support for `avif`, `heic`, `heif`, and `jxl` MIME types.
- `WebPush` default TTL as a string (`2419200`) for Guzzle PSR-7 2.11+ header compatibility.
- `WebPush::batch()` report rows include `expired` via `isSubscriptionExpired()` so callers can deactivate dead endpoints.

### Fixed
- `FileValidator` MIME checks now use `UploadFile::getMimeType()` (finfo + extension fallback) so modern image formats are not rejected when finfo returns `application/octet-stream`.
- `PointType` docs and `toParams()` object/named-array handling align with MySQL lon-first (`X` = longitude, `Y` = latitude). String `"x y"` order from `fromDb()` is unchanged.

### Changed
- Security upgrade guide documents ApiRouter auto-reset and SSE timing notes.
- Custom data type docs document lon-first `PointType` inputs.

## [1.3.48] - 2026-08-13

### Added
- `PointType::fromDb()` decodes MySQL POINT WKB (with SRID prefix) to an `"x y"` string.
