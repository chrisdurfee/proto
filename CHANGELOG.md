# Changelog

All notable changes to Proto Framework are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
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
