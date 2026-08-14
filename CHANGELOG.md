# Changelog

All notable changes to Proto Framework are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
