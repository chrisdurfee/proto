# Changelog

All notable changes to Proto Framework are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
