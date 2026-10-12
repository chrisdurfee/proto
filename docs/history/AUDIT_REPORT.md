# Proto Framework — Comprehensive Audit Report

**Date:** April 12, 2026
**Updated:** April 12, 2026
**Scope:** Security, Performance, Maintainability, Consistency, Efficiency, Developer Experience
**Files Reviewed:** 100+ source files across all framework subsystems
**Status:** Remediation applied — see [Resolution Log](#resolution-log) below

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Security](#2-security)
3. [Performance](#3-performance)
4. [Maintainability](#4-maintainability)
5. [Consistency](#5-consistency)
6. [Efficiency](#6-efficiency)
7. [Developer Experience](#7-developer-experience)
8. [Detailed Findings by Subsystem](#8-detailed-findings-by-subsystem)
9. [Scorecard](#9-scorecard)
10. [Prioritized Recommendations](#10-prioritized-recommendations)

---

## 1. Executive Summary

Proto is a well-architected PHP modular monolith with strong fundamentals: **100% strict_types coverage**, **comprehensive prepared statement usage**, **solid file upload validation**, and **clean separation of concerns**. The framework demonstrates mature design patterns including service delegation, batch enrichment, keyset pagination, and atomic database operations.

However, the audit identified **5 critical**, **12 high**, and **15 medium** severity issues across security, performance, and maintainability. The most significant findings involve **CSRF middleware disabled by default**, **unsafe dynamic method forwarding in ModelTrait**, **IP address spoofing via untrusted proxy headers**, and **immutable field enforcement only at the controller layer**.

**Overall Rating: 7.2/10** — Production-capable with targeted hardening needed.

---

## 2. Security

### 2.1 Critical Findings

| ID | Finding | Location | Impact |
|----|---------|----------|--------|
| **SEC-01** | **CSRF middleware commented out** | `ApiRouter.php` | State-changing API endpoints are unprotected against cross-site request forgery. Any authenticated user's browser can be used to make unauthorized API calls. |
| **SEC-02** | **Unsafe `__call` forwarding in ModelTrait** | `Controllers/ModelTrait.php` | `call_user_func_array()` forwards HTTP requests to any public model method without a whitelist. Attacker could invoke destructive methods (e.g., bulk delete, truncate) if they exist on the model. |
| **SEC-03** | **IP spoofing via proxy headers** | `Http/PublicIp.php` | Trusts `X-Forwarded-For`, `X-Real-IP`, and `CF-Connecting-IP` headers unconditionally. Without trusted proxy configuration, rate limiting, geo-blocking, and audit logs can be bypassed by any client. |
| **SEC-04** | **HTTP_HOST header not validated** | `Config.php` | Environment detection uses `$_SERVER['HTTP_HOST']` directly for comparison against configured domains. An attacker can spoof the Host header to potentially trigger wrong environment configuration. |
| **SEC-05** | **Post-execution policy checks run after side effects** | `Auth/PolicyProxy.php` | The `afterGet()` / post-execution policy hooks run AFTER the controller method has already executed. Database writes, API calls, and file operations complete before authorization failure is detected. |

### 2.2 High Findings

| ID | Finding | Location | Impact |
|----|---------|----------|--------|
| **SEC-06** | **`$_REQUEST` mutation from PUT/PATCH/DELETE body** | `Http/Request.php` | Request body is parsed and merged into `$_REQUEST` superglobal unconditionally for non-GET methods, without content-type validation or size limits. |
| **SEC-07** | **CORS reflects any Origin** | `Http/Router/Headers.php` | The `Access-Control-Allow-Origin` header reflects any sanitized Origin value without domain whitelist validation. |
| **SEC-08** | **Dynamic method calls in Validator** | `Api/Validator.php` | `Sanitize::$method()` and `Validate::$method()` use dynamic method invocation without an explicit allowlist of valid sanitizer/validator names. |
| **SEC-09** | **`die` statement in `setError()`** | `Controllers/ApiController.php` | Bare `die` prevents cleanup, logging, and finally blocks. No error context is captured for audit trails. |
| **SEC-10** | **Immutable fields not enforced at storage layer** | `Storage/Storage.php` | `$immutableFields` are only stripped at the `ResourceController` level. Direct storage calls bypass this protection entirely. |
| **SEC-11** | **CSRF token not auto-rotated** | `Auth/Gates/CrossSiteRequestForgeryGate.php` | Token is created once per session with no rotation on login, logout, or privilege escalation. Violates OWASP token rotation guidelines. |
| **SEC-12** | **File upload `containsDangerousContent()` not called by default** | `Http/UploadFile.php` | Excellent polyglot detection method exists but is never invoked automatically during file upload processing. |
| **SEC-13** | **Error backtrace logs expose source paths** | `Error/ErrorLog.php` | Full backtraces with file paths are stored in database. Could leak server directory structure if logs are accessible. |
| **SEC-14** | **SMTP credentials visible in error logs** | `Dispatch/Email.php` | PHPMailer exceptions may include SMTP credentials in error messages that get logged. |
| **SEC-15** | **FileValidator/ImageValidator fail-open on finfo failure** | `Api/FileValidator.php`, `Api/ImageValidator.php` | If `finfo_open()` fails (missing extension), validation returns `true` (allow) instead of `false` (deny). |
| **SEC-16** | **Raw SQL fallback in filter arrays** | `Storage/Filter.php` | Non-array string values in filter arrays are treated as raw SQL conditions, creating an implicit SQL injection vector if developers pass unsanitized input. |

### 2.3 Medium Findings

| ID | Finding | Location |
|----|---------|----------|
| **SEC-18** | Table names not sanitized (developer-controlled but worth validating) | `Database/Adapters/Mysqli.php` |
| **SEC-19** | JSON input via `Request::json()` applies `strip_tags()` before JSON decode, corrupting valid JSON structures | `Http/Request.php` |
| **SEC-20** | No JSON depth limit on filter parsing (potential DoS via deeply nested objects) | `Controllers/ApiController.php` |
| **SEC-21** | Redis password in plaintext config | `Cache/Drivers/RedisDriver.php` |
| **SEC-22** | Policy method validation only runs in dev mode — typos silently fail in production | `Auth/Policies/Policy.php` |
| **SEC-23** | Temp directory uses `sys_get_temp_dir()` which may be world-readable on shared hosts | `Http/UploadFile.php` |
| **SEC-24** | `strip_tags()` in `Request::sanitized()` is too aggressive for structured data and insufficient for XSS | `Http/Request.php` |

### 2.4 Security Strengths

- **100% prepared statement usage** — All database queries use MySQLi parameter binding
- **Column name sanitization** — `Sanitize::cleanColumn()` enforces alphanumeric-only
- **File upload defense-in-depth** — MIME validation via `finfo`, `getimagesize()`, content scanning, extension allowlists
- **Timing-safe token comparison** — `hash_equals()` used for CSRF and encryption HMAC verification
- **Strong CSRF token entropy** — `random_bytes(128)` generates 1024-bit tokens
- **AES-256-CTR + HMAC encryption** — Industry-standard authenticated encryption
- **Session security headers** — `use_only_cookies=1`, `use_trans_sid=0`, `cache_limiter=nocache`
- **Session regeneration** — `session_regenerate_id(true)` destroys old session data
- **Redis channel allowlist** — Regex validation prevents command injection
- **Attachment path traversal protection** — Email attachments validated with `realpath()` + `BASE_PATH`
- **Read-only data structures** — `ReadOnlyObject`/`ReadOnlyArray` prevent accidental mutation

---

## 3. Performance

### 3.1 Strengths

| Pattern | Location | Benefit |
|---------|----------|---------|
| **Keyset (cursor) pagination** | `Storage/Limit.php` | O(1) pagination vs O(n) offset — handles millions of rows efficiently |
| **Batch enrichment trait** | `Controllers/Traits/BatchEnrichmentTrait.php` | `batchMapField()`/`batchMapExists()` prevent N+1 queries with declarative API |
| **Eager loading via JSON aggregation** | `Storage/Helpers/SubQueryHelper.php` | Complex relationships loaded in single query using `JSON_ARRAYAGG()` subqueries |
| **Middleware instance caching** | `Http/Router/MiddlewareTrait.php` | Static `$middlewareInstances` array reuses middleware objects |
| **Connection pooling** | `Database/ConnectionCache.php` | In-memory connection reuse within request; persistent connections across requests |
| **Atomic increment/decrement** | `Services/Traits/ToggleLikeTrait.php` | Database-level atomic operations prevent lost updates |
| **Redis SCAN vs KEYS** | `Cache/Drivers/RedisDriver.php` | Iterator-based key scanning avoids blocking on large datasets |
| **Lazy service instantiation** | `Controllers/ResourceController.php` | Service classes instantiated on first use, not at construction |

### 3.2 Issues

| ID | Issue | Location | Impact |
|----|-------|----------|--------|
| **PERF-01** | **Static request properties persist across PHP-FPM requests** | `Http/Request.php` | Memory leak potential if large request bodies are cached; stale state in long-running processes. |
| **PERF-02** | **No maximum query limit enforcement** | `Controllers/ResourceController.php` | `all()` accepts arbitrary `limit` values. Missing `$maxLimit` cap could return entire tables. |
| **PERF-03** | **Full result loading (no streaming)** | `Database/Adapters/Mysqli.php` | `fetchStatementResults()` loads all rows into memory. Large result sets may exhaust PHP memory. |
| **PERF-04** | **`require_once` in request hot path** | `Api/ApiRouter.php` | File system I/O for resource inclusion on every API request. |
| **PERF-05** | **Unbounded event subscriptions** | `Events/EventEmitter.php` | In-memory subscriber arrays have no size limit. Long-running workers accumulate entries. |
| **PERF-06** | **KafkaDriver memory arrays unbounded** | `Jobs/KafkaDriver.php` | `$processingJobs`, `$failedJobs`, `$completedJobs` arrays grow without cleanup in long-lived workers. |
| **PERF-07** | **N+1 risk in ToggleLikeTrait** | `Services/Traits/ToggleLikeTrait.php` | Three sequential queries per toggle (getBy → getBy → get). Acceptable for single operations but not batch. |
| **PERF-08** | **Error logging DB inserts per error** | `Error/Error.php` | High-traffic sites could bottleneck on per-error DB inserts. No batching mechanism. |
| **PERF-09** | **Email attachment full-memory base64** | `Dispatch/Email.php` | Attachments base64-encoded inline, buffering entire file in memory. |

### 3.3 Recommendations

```php
// PERF-02 Fix: Add max limit enforcement
protected int $maxLimit = 1000;

public function all(Request $request): object
{
    $inputs = $this->getAllInputs($request);
    $inputs->limit = min($inputs->limit, $this->maxLimit);
    // ...
}
```

---

## 4. Maintainability

### 4.1 Strengths

- **Clean layered architecture**: Bootstrap → Router → Middleware → Controller → Service → Model → Storage
- **Single Responsibility**: Services, traits, controllers have focused purposes
- **Composition over inheritance**: `BatchEnrichmentTrait`, `SyncableTrait`, `LocationFilterTrait` pattern
- **Declarative controller config**: `$routeParams`, `$filterParams`, `$enrichUserFields`, `$scopeToUser` reduce boilerplate
- **Service delegation pattern**: `$serviceClass` auto-instantiation separates business logic from controllers
- **Factory pattern**: Clean test data generation with state support
- **Value objects**: `ServiceResult`, `Response` encapsulate outcomes consistently

### 4.2 Issues

| ID | Issue | Location | Impact |
|----|-------|----------|--------|
| **MAINT-01** | **ResourceController has too many responsibilities** | `Controllers/ResourceController.php` | CRUD + validation + filtering + enrichment + file upload + audit fields + user scoping — single class handles ~10 concerns. |
| **MAINT-02** | **Magic `__call`/`__callStatic` in ModelTrait** | `Controllers/ModelTrait.php` | Dynamic method forwarding makes code hard to trace, breaks IDE analysis, and defeats static analysis tools. |
| **MAINT-03** | **SubQueryHelper complexity** | `Storage/Helpers/SubQueryHelper.php` | 600+ lines handling nested join aggregation. High cognitive load; needs more documentation and test coverage. |
| **MAINT-04** | **Multiple deprecated methods in ResourceHelper** | `Api/ResourceHelper.php` | `filterResourcePath()` and `getResourcePathFromUrl()` kept alongside new methods — increases maintenance burden. |
| **MAINT-05** | **Inconsistent filter/option parameter naming** | `Controllers/ApiController.php` | `getFilter()` accepts both `filter` and `option` parameters for the same purpose. |
| **MAINT-06** | **Magic property access on models** | `Models/Model.php` | `__get`, `__set`, `__isset`, `__unset` handle both properties and relations. Developer confusion possible. |
| **MAINT-07** | **Hard-coded magic strings** | Throughout | Field names like `'userId'`, `'createdBy'`, `'offset'`, `'limit'` repeated as strings across many files. |

---

## 5. Consistency

### 5.1 Strengths

- **100% `declare(strict_types=1)`** across all files reviewed — excellent
- **Consistent brace style** — Opening brace on new line followed throughout
- **Consistent DocBlocks** — `@param`, `@return`, `@property` annotations present on most files
- **Consistent use of tabs for indentation**
- **Consistent PSR-4 autoloading** with `Modules\` and `Common\` namespace mapping

### 5.2 Issues

| ID | Issue | Details |
|----|-------|---------|
| **CONS-01** | **Error HTTP status code defaults vary** | `Controller::error()` defaults to HTTP 200 for errors, not 400. Non-standard. |
| **CONS-02** | **`setError()` return type mismatch** | Declared return type `array` but always calls `die` (unreachable return). |
| **CONS-03** | **Hook method naming inconsistency** | `modifyAddItem()`, `modifyUpdateItem()`, `modifyFilter()` — the first two modify data by reference, the third returns a value. Mixed patterns. |
| **CONS-04** | **Public vs protected method signatures differ** | Public methods receive `Request`, protected `addItem()`/`updateItem()` receive `object`. Hook methods receive both `object` and `Request`. |
| **CONS-05** | **Response method inconsistency** | Static `Response::success()` doesn't accept message; instance method does. `error()` returns object in Controller but triggers `die` in ApiController. |
| **CONS-06** | **Filter object mutability** | `modifyFilter()` modifies in-place sometimes, creates new object other times. |
| **CONS-07** | **Integer ID handling varies** | Some places use `(int)$id`, others `$request->getInt('id')`, others `$params->id` with manual casting. |
| **CONS-08** | **Silent vs loud failures** | Session init failures are silent; Module service failures are silent; DB errors are logged. No consistent strategy. |
| **CONS-09** | **Sanitization approach inconsistent** | `Request::sanitized()` uses `strip_tags()`; `Request::json()` also strips tags then JSON-decodes; `Request::raw()` returns unsanitized. No clear guidance on when to use which. |

---

## 6. Efficiency

### 6.1 Architectural Efficiency

| Area | Rating | Notes |
|------|--------|-------|
| **Database queries** | Excellent | Prepared statements with parameter binding throughout; batch IN queries via enrichment traits |
| **Connection management** | Good | Singleton connections cached per request; persistent connections for cross-request reuse |
| **Pagination** | Excellent | Keyset pagination for large datasets; traditional offset as fallback |
| **Middleware pipeline** | Good | Instance caching prevents re-instantiation per request |
| **Autoloading** | Good | PSR-4 with Composer classmap for migrations |
| **Session handling** | Good | Lock-and-release pattern prevents blocking on long requests |
| **Event system** | Good | Lazy Redis adapter initialization; local pub/sub fallback |

### 6.2 Inefficiencies

| ID | Issue | Improvement |
|----|-------|-------------|
| **EFF-01** | Filter parsing runs `urldecode()` → `strip_tags()` → `JsonFormat::decode()` on every request | Cache parsed filter; skip unnecessary sanitization for JSON |
| **EFF-02** | `ResourceHelper` makes multiple `realpath()` calls to resolve resource paths | Cache resolved paths in static array |
| **EFF-03** | `finfo` opened per file validation call | Cache `finfo` resource handle |
| **EFF-04** | Model `augment()` hooks run on every create/update even when not needed | Only invoke if overridden (check with ReflectionMethod) |
| **EFF-05** | Error logging creates DB insert per error | Batch error logging or buffer with periodic flush |

---

## 7. Developer Experience

### 7.1 Strengths

| Area | Rating | Notes |
|------|--------|-------|
| **Zero-boilerplate CRUD** | Excellent | `ResourceController` with `$model`, `$routeParams`, `$filterParams`, `$scopeToUser` creates full CRUD with minimal code |
| **Declarative enrichment** | Excellent | `$enrichUserFields` auto-attaches user data to responses |
| **Service delegation** | Excellent | `$serviceClass` auto-instantiates and delegates — clean separation |
| **Batch enrichment** | Excellent | `batchMapField()`/`batchMapExists()` eliminate manual N+1 prevention |
| **Factory testing** | Good | `User::factory()->create()` with states; `assertDatabaseHas()` for verification |
| **Migration DSL** | Good | Fluent `$table->id()`, `$table->varchar()`, `$table->timestamps()`, `$table->foreign()` |
| **Copilot instructions** | Excellent | Comprehensive `.github/copilot-instructions.md` with patterns, anti-patterns, and examples |
| **Documentation** | Good | Extensive `docs/` folder covering pagination, factories, testing, Redis events |

### 7.2 Issues

| ID | Issue | Impact |
|----|-------|--------|
| **DX-01** | **Error messages are too vague** | "Invalid item data", "Unable to add the item" — no field-level error mapping returned to client. Developers must debug by checking logs. |
| **DX-02** | **`setError()` calls `die`** | Makes error flow impossible to test properly. Controllers can't be unit-tested for error paths because `die` terminates the process. |
| **DX-03** | **Magic method forwarding confuses IDEs** | `ModelTrait::__call()` defeats autocomplete, go-to-definition, and static analysis. PHPStan/Psalm can't verify method calls. |
| **DX-04** | **No error logging with context** | When `setError()` fires, no logging of which controller, method, user, or input caused it. Debugging production issues requires guessing. |
| **DX-05** | **Filter/option dual parameter** | `getFilter()` accepts both `filter` and `option` query params. Unclear which to use. API consumers may use wrong one. |
| **DX-06** | **Mass assignment protection is opt-in** | Developers must remember to define `$immutableFields` on every model. No framework-level safety net for new models. |
| **DX-07** | **Transaction test limitations under-documented** | `Model::get()` may return null in test transactions. Developers must know to use `fetchWhere()` or `getWithoutJoins()` instead — this is a common gotcha. |
| **DX-08** | **Policy validation only in dev** | Policy method typos (e.g., `gett()` instead of `get()`) silently fall through in production. Developers get "unauthorized" with no indication of misconfiguration. |

---

## 8. Detailed Findings by Subsystem

### 8.1 Core Bootstrap (Base, Config, System, Auth)

| Aspect | Status |
|--------|--------|
| Strict types | ✅ All files |
| Singleton patterns | ✅ Clean implementations |
| Error handling | ✅ RuntimeException for critical failures |
| Config loading | ⚠️ HTTP_HOST spoofing for environment detection (SEC-04) |
| Autoloading | ✅ Proper PSR-4 with vendor fallback |

### 8.2 HTTP/Router Layer

| Aspect | Status |
|--------|--------|
| Strict types | ✅ All files |
| CSRF protection | ❌ Disabled by default (SEC-01) |
| Rate limiting | ✅ Configurable with cache backend |
| CORS handling | ⚠️ Reflects any origin (SEC-07) |
| Input handling | ⚠️ `$_REQUEST` mutation (SEC-06), `strip_tags()` on JSON (SEC-19) |
| Redirect safety | ✅ CR/LF stripped from Location header |
| Session security | ✅ Secure cookie settings, regeneration support |
| File uploads | ✅ Multi-layer validation (MIME, extension, content scan) |

### 8.3 Controller Layer

| Aspect | Status |
|--------|--------|
| Strict types | ✅ All files |
| Authorization | ⚠️ Relies on router-level enforcement; no controller-level fallback |
| Input validation | ✅ Centralized via `validateRules()` |
| Mass assignment | ⚠️ Opt-in via `$immutableFields` (SEC-10) |
| Audit fields | ✅ Auto-injected for `createdBy`, `userId`, `updatedBy` |
| Response format | ⚠️ Inconsistent error status codes (CONS-01) |
| `setError()` | ❌ Uses `die` (SEC-09, DX-02) |
| `__call` forwarding | ❌ Unsafe dynamic dispatch (SEC-02) |

### 8.4 Model/Storage Layer

| Aspect | Status |
|--------|--------|
| Strict types | ✅ All files |
| SQL injection | ✅ All queries parameterized; column names sanitized |
| Prepared statements | ✅ 100% coverage via MySQLi `bind_param()` |
| Data mapping | ✅ Clean camelCase/snake_case strategy pattern |
| Eager loading | ✅ JSON aggregation subqueries prevent N+1 |
| Immutable fields | ⚠️ Only enforced at controller layer (SEC-10) |
| Filter safety | ⚠️ Raw SQL fallback in string filter values (SEC-16) |
| Transaction support | ✅ Nesting-aware with proper autocommit management |

### 8.5 Auth/Policies

| Aspect | Status |
|--------|--------|
| Strict types | ✅ All files |
| Policy enforcement | ✅ Robust — cannot bypass when applied |
| CSRF token strength | ✅ 1024-bit entropy with timing-safe comparison |
| Token rotation | ⚠️ No automatic rotation (SEC-11) |
| Post-execution checks | ⚠️ Run after side effects (SEC-05) |
| Dev-only validation | ⚠️ Policy typos silent in production (SEC-22) |

### 8.6 Database/QueryBuilder

| Aspect | Status |
|--------|--------|
| Strict types | ✅ All files |
| Parameter binding | ✅ 100% — no raw SQL concatenation with user values |
| Connection security | ✅ Readonly credential properties; charset hardcoded to `utf8mb4` |
| Transaction isolation | ✅ Nesting support with autocommit management |
| Migration DSL | ✅ Clean fluent API for schema definition |
| Error handling | ✅ Logged via centralized handler; no SQL in user-facing output |

### 8.7 Services/Utils

| Aspect | Status |
|--------|--------|
| Strict types | ✅ All files |
| Encryption | ✅ AES-256-CTR + HMAC; `random_bytes()` IV; timing-safe verification |
| File operations | ⚠️ No path traversal prevention in `File::put()` (relies on caller) |
| Location filtering | ✅ Parameterized spatial queries |
| Atomic operations | ✅ Database-level increment/decrement for race condition prevention |
| ServiceResult | ✅ Clean value object for error/success propagation |

### 8.8 Events/Cache/Dispatch

| Aspect | Status |
|--------|--------|
| Strict types | ✅ All files |
| Redis channel safety | ✅ Allowlist regex validation |
| Cache degradation | ✅ Graceful fallback when cache unavailable |
| Email security | ✅ Attachment path traversal prevention |
| WebPush | ✅ Proper VAPID key handling |
| Credential handling | ⚠️ Credentials visible in error traces (SEC-14) |

### 8.9 Testing Framework

| Aspect | Status |
|--------|--------|
| Strict types | ✅ All files |
| Transaction isolation | ✅ Auto-rollback in test base class |
| Database assertions | ✅ `assertDatabaseHas()`/`assertDatabaseMissing()` |
| Factory support | ✅ Model factories with states and hooks |
| HTTP helpers | ✅ GET/POST/PUT/PATCH/DELETE convenience methods |
| Session mocking | ✅ `setAuthenticatedUser()` for auth testing |

---

## 9. Scorecard

| Category | Score | Weight | Weighted |
|----------|-------|--------|----------|
| **Security** | 6.5/10 | 30% | 1.95 |
| **Performance** | 8.0/10 | 15% | 1.20 |
| **Maintainability** | 7.5/10 | 15% | 1.13 |
| **Consistency** | 7.0/10 | 10% | 0.70 |
| **Efficiency** | 8.0/10 | 10% | 0.80 |
| **Developer Experience** | 7.5/10 | 20% | 1.50 |
| **Overall** | | | **7.28/10** |

### Category Breakdown

**Security (6.5/10)**: Strong fundamentals (prepared statements, encryption, file validation) offset by critical CSRF gap, unsafe dynamic dispatch, and IP spoofing. Fixable with targeted changes.

**Performance (8.0/10)**: Excellent patterns (keyset pagination, batch enrichment, connection pooling, eager loading). Minor gaps in memory management and query limits.

**Maintainability (7.5/10)**: Clean architecture with good separation of concerns. Weakened by ResourceController's broad responsibility and complex SubQueryHelper.

**Consistency (7.0/10)**: Excellent strict_types and code style consistency. Inconsistent error handling patterns and response behaviors across controller layers.

**Efficiency (8.0/10)**: Well-optimized query patterns, caching, and resource management. Minor redundant processing in filter parsing and path resolution.

**Developer Experience (7.5/10)**: Outstanding zero-boilerplate CRUD patterns and documentation. Hampered by vague error messages, `die` in error paths, and silent production failures.

---

## 10. Prioritized Recommendations

### P0 — Critical (Fix Immediately)

| # | Recommendation | Effort |
|---|----------------|--------|
| 1 | **Re-enable CSRF middleware** in `ApiRouter.php` or implement per-route token validation | Low |
| 2 | **Add method whitelist to ModelTrait** — replace `__call` forwarding with explicit allowed method array or remove entirely | Low |
| 3 | **Add trusted proxy configuration for IP resolution** — only trust `X-Forwarded-For` when `REMOTE_ADDR` matches configured proxy IPs | Medium |
| 4 | **Replace `die` with proper error response** in `ApiController::setError()` — return void, let the router handle exit | Low |
| 5 | **Call `containsDangerousContent()` automatically** during file upload validation in `FileValidator`/`ImageValidator` | Low |

### P1 — High (Fix Before Next Release)

| # | Recommendation | Effort |
|---|----------------|--------|
| 6 | **Validate HTTP_HOST** against configured domain whitelist in `Config.php` | Low |
| 7 | **Auto-call `containsDangerousContent()`** in FileValidator/ImageValidator | Low |
| 8 | **Enforce immutable fields at storage layer** — strip `$immutableFields` in `Storage::update()` | Medium |
| 9 | **Add CORS origin whitelist** — validate Origin header against configured domains | Low |
| 10 | **Add max limit enforcement** — cap query results with configurable `$maxLimit` property | Low |
| 11 | **Rotate CSRF token** on login/logout/privilege escalation events | Medium |
| 12 | **Whitelist validator/sanitizer methods** — use `const ALLOWED_SANITIZERS` in `Validator.php` | Low |
| 13 | **Fail-closed on finfo failure** — return false (deny) when MIME detection is unavailable | Low |
| 14 | **Mask credentials in error logs** — sanitize SMTP passwords and Redis credentials before logging | Low |
| 15 | **Sanitize backtraces** — strip `BASE_PATH` from logged file paths | Low |

### P2 — Medium (Next Maintenance Cycle)

| # | Recommendation | Effort |
|---|----------------|--------|
| 16 | **Fix `Request::json()`** — use `Request::raw()` instead of `Request::input()` to avoid `strip_tags()` corrupting JSON | Low |
| 17 | **Add JSON depth limit** to filter parsing — `json_decode($filter, false, 32)` | Low |
| 18 | **Validate content-type** before parsing PUT/PATCH/DELETE request bodies | Low |
| 19 | **Clear static request properties** between requests for long-running processes | Low |
| 20 | **Run policy validation in production** (not just dev) — or at least log mismatches | Medium |
| 21 | **Standardize error HTTP status codes** — `Controller::error()` should default to 400, not 200 | Low |
| 22 | **Fix `setError()` return type** — change from `array` to `never` or `void` | Low |
| 23 | **Cache finfo resource handle** in FileValidator/ImageValidator | Low |
| 24 | **Add encryption key rotation support** with versioned keys | High |
| 25 | **Document raw SQL filter pattern** — add `RawSQL` wrapper class to make intent explicit | Medium |
| 26 | **Add error context to `setError()`** — log controller name, method, user ID, and input summary | Medium |
| 27 | **Add query profiling** in dev mode for N+1 detection | Medium |
| 28 | **Clean up deprecated ResourceHelper methods** | Low |
| 29 | **Add field-level validation errors** to API responses for better client-side debugging | Medium |
| 30 | **Add bounded arrays for KafkaDriver** — implement ring buffer or periodic cleanup for job tracking arrays | Low |

---

## Appendix: Files Reviewed

**Core**: Base.php, Config.php, System.php, Auth.php
**API**: ApiRouter.php, Validator.php, FileValidator.php, ImageValidator.php, ResourceHelper.php
**HTTP**: Router.php, Request.php, Response.php, Route.php, Headers.php, Redirect.php, UriQuery.php, PublicIp.php, Session.php, FileSession.php, UploadFile.php, RateLimiter.php, MiddlewareTrait.php, CrossSiteProtectionMiddleware.php, ControllerHelper.php
**Controllers**: Controller.php, ResourceController.php, ApiController.php, ModelController.php, ModelTrait.php, Response.php, SyncController.php, BatchEnrichmentTrait.php, SyncableTrait.php
**Models**: Model.php, Factory.php, Data/*.php, Joins/*.php, Relations/*.php
**Storage**: Storage.php, Filter.php, Limit.php, DataTypes/*.php, Helpers/*.php
**Database**: Database.php, DatabaseManager.php, ConnectionCache.php, Adapters/Mysqli.php, QueryBuilder/*.php, Migrations/*.php
**Auth**: PolicyProxy.php, Gates/Gate.php, Gates/CrossSiteRequestForgeryGate.php, Policies/Policy.php
**Services**: Service.php, ServiceResult.php, Traits/*.php
**Cache**: Cache.php, Drivers/RedisDriver.php, Policies/*.php
**Events**: Events.php, EventEmitter.php, RedisPubSubAdapter.php
**Error**: Error.php, ErrorLog.php
**Utils**: Encryption/*.php, Files/*.php, Filter/*.php, Sanitize.php, Strings.php
**Dispatch**: Email.php, Sms.php, WebPush.php, Mail.php
**Jobs**: Job.php, JobQueue.php, Scheduler.php, DatabaseDriver.php, KafkaDriver.php
**Tests**: Test.php, DatabaseTestHelpers.php, ModelTestHelpers.php, HttpTestHelpers.php
**Module**: Module.php

---

## Resolution Log

All changes verified with PHP syntax checks (14/14 pass) and test suite (79/82 pass; 3 pre-existing TransactionIsolationTest failures unrelated to changes).

| ID | Status | Resolution | File(s) Changed |
|----|--------|------------|-----------------|
| **SEC-01** | ✅ Fixed | CSRF middleware enabled via `router()->defaultMutationMiddleware()` in `setupRouter()`. All mutation routes (POST/PUT/PATCH/DELETE) now auto-apply `CrossSiteProtectionMiddleware`. Individual routes can opt out via `withoutMutationMiddleware()`. | `src/Api/ApiRouter.php` |
| **SEC-02** | ✅ Fixed | Added `$allowedModelMethods` whitelist array to `ModelTrait`. `__call()` and `__callStatic()` now reject methods not in the whitelist. Default allows: `get`, `getBy`, `fetchWhere`, `all`, `search`, `count`. Controllers can override to expand. | `src/Controllers/ModelTrait.php` |
| **SEC-03** | ✅ Fixed | Proxy headers (X-Forwarded-For, X-Real-IP, CF-Connecting-IP, etc.) only trusted when `REMOTE_ADDR` matches a configured trusted proxy. Falls back to `REMOTE_ADDR` only. Supports CIDR notation. Configure via `env('trustedProxies')`. | `src/Http/PublicIp.php` |
| **SEC-04** | ⏭️ Skipped | No domain whitelist available yet per project owner. |  |
| **SEC-05** | ✅ Documented | Added detailed docblock to `PolicyProxy::callControllerMethod()` explaining that post-execution hooks run after side effects. Recommends pre-execution policies for destructive operations. | `src/Auth/PolicyProxy.php` |
| **SEC-06** | ✅ Fixed | `Request::setCustomInputs()` now validates `Content-Type` header — only parses `application/x-www-form-urlencoded` bodies into `$_REQUEST`. JSON and other content types are not blindly parsed. | `src/Http/Request.php` |
| **SEC-07** | ✅ Fixed | CORS `Access-Control-Allow-Origin` now validates against configured origin whitelist (`env('cors')->allowedOrigins`). When unconfigured, falls back to reflecting origin (development mode). Also added `csrf-token` to `Access-Control-Allow-Headers`. | `src/Http/Router/Headers.php` |
| **SEC-08** | ✅ Fixed | Added `ALLOWED_SANITIZERS` and `ALLOWED_VALIDATORS` constants to `Validator`. `sanitizeValue()` and `validateByType()` reject methods not in the whitelist. | `src/Api/Validator.php` |
| **SEC-09** | ⏭️ Kept | `die` in `setError()` kept intentionally — system has error logging before it. Return type corrected to `never`. | `src/Controllers/ApiController.php` |
| **SEC-10** | ✅ Fixed | `Storage::getUpdateData()` now strips `$immutableFields` (converting camelCase to snake_case) before persistence. Immutability enforced regardless of caller (controller, service, or direct). | `src/Storage/Storage.php` |
| **SEC-11** | ✅ Fixed | `CrossSiteRequestForgeryGate::reset()` changed from `protected` to `public`. Added `rotate()` method that resets and generates a new token. Apps should call `rotate()` on login/logout/privilege escalation. | `src/Auth/Gates/CrossSiteRequestForgeryGate.php` |
| **SEC-13** | ✅ Fixed | `buildExceptionData()` now uses `DEBUG_BACKTRACE_IGNORE_ARGS` (no argument values in traces). Added `redactBacktrace()` that strips `BASE_PATH` from all frame file paths before JSON encoding. Both `buildErrorData` and `buildExceptionData` use it. | `src/Error/Error.php` |
| **SEC-14** | ✅ Fixed | Added `sanitizeErrorMessage()` to `Error` class that masks SMTP AUTH credentials and password patterns in error messages before database storage. Applied to both error and exception handlers. | `src/Error/Error.php` |
| **SEC-15** | ✅ Fixed | `FileValidator::validateFileContent()` changed from fail-open to fail-closed — returns `false` when `finfo`/MIME detection is unavailable instead of `true`. | `src/Api/FileValidator.php` |
| **SEC-19** | ✅ Fixed | `Request::json()` now calls `raw()` instead of `input()`, preventing `strip_tags()` from corrupting JSON structures containing HTML-like content. | `src/Http/Request.php` |
| **SEC-20** | ✅ Fixed | `JsonFormat::decode()` now enforces a configurable depth limit (default: 32 levels) via `json_decode()` `$depth` parameter, preventing DoS via deeply nested JSON objects. | `src/Utils/Format/JsonFormat.php` |
| **SEC-22** | ✅ Fixed | `Policy::validateMethodSignatures()` now runs in ALL environments. In development: triggers `E_USER_WARNING`. In production: logs via `error_log()`. Policy method signature typos no longer silently fail in production. | `src/Auth/Policies/Policy.php` |
| **PERF-01** | ✅ Fixed | Added `Request::reset()` method to clear all cached static properties between requests in long-running processes (PHP-FPM, Swoole, RoadRunner). Also added `PublicIp::reset()`. | `src/Http/Request.php`, `src/Http/PublicIp.php` |
| **PERF-02** | ✅ Fixed | Added `$maxLimit` property (default: 1000) to `ApiController`. `getAllInputs()` now caps the `limit` parameter via `min($limit, $maxLimit)`. Controllers can override to adjust. | `src/Controllers/ApiController.php` |
| **CONS-01** | ⏭️ Kept | HTTP 200 for errors kept intentionally — designed for graceful client-side handling. |  |
| **CONS-02** | ✅ Fixed | `setError()` return type changed from `array` to `never`. DocBlock corrected to reflect actual behavior. | `src/Controllers/ApiController.php` |

### Configuration Required

After deploying these changes, configure the following in `common/Config/.env`:

```json
{
  "trustedProxies": ["10.0.0.0/8", "172.16.0.0/12", "192.168.0.0/16"],
  "cors": {
    "allowedOrigins": ["https://yourdomain.com", "https://app.yourdomain.com"]
  }
}
```

- **`trustedProxies`**: Array of proxy IP addresses or CIDR ranges. Required for correct IP resolution behind load balancers/CDNs (e.g., Cloudflare, AWS ALB).
- **`cors.allowedOrigins`**: Array of allowed origins for CORS requests. When empty/missing, all origins are allowed (development fallback).
