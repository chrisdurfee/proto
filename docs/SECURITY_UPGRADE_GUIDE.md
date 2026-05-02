# Proto Framework — Security Upgrade Guide

**Version**: April 2026 Security Hardening Release
**Applies to**: Proto core (`src/`) and all apps using Proto as a dependency

---

## Table of Contents

1. [Overview](#overview)
2. [Breaking Changes](#breaking-changes)
3. [Required Configuration](#required-configuration)
4. [Migration Checklist](#migration-checklist)
5. [Feature Details](#feature-details)
   - [CSRF Protection](#1-csrf-protection-on-mutation-routes)
   - [ModelTrait Method Whitelist](#2-modeltrait-method-whitelist)
   - [Trusted Proxy Configuration](#3-trusted-proxy-configuration)
   - [CORS Origin Whitelist](#4-cors-origin-whitelist)
   - [Input Validation Whitelist](#5-input-validation-whitelist)
   - [File Upload Fail-Closed](#6-file-upload-fail-closed)
   - [Request Body Parsing](#7-request-body-parsing-content-type-enforcement)
   - [JSON Depth Limit](#8-json-depth-limit)
   - [Immutable Fields at Storage Layer](#9-immutable-fields-at-storage-layer)
   - [CSRF Token Rotation](#10-csrf-token-rotation)
   - [Query Limit Enforcement](#11-query-limit-enforcement)
   - [Error Log Sanitization](#12-error-log-sanitization)
   - [Policy Validation in Production](#13-policy-validation-in-production)
   - [Long-Running Process Support](#14-long-running-process-support)
6. [FAQ](#faq)

---

## Overview

This release hardens the Proto framework with security, performance, and consistency improvements. Most changes are transparent, but a few require configuration or code updates in consuming applications.

**Key themes:**
- CSRF protection enabled by default on all mutation routes
- Proxy header trust restricted to configured IPs
- Input validation and file upload hardened
- Query limits enforced to prevent unbounded reads
- Error logs sanitized to prevent credential leakage

---

## Breaking Changes

### High Impact

| Change | What Breaks | Who's Affected | Fix |
|--------|------------|----------------|-----|
| **CSRF middleware on mutations** | All POST/PUT/PATCH/DELETE requests without a CSRF token will be rejected | Every app using Proto's router | See [CSRF Protection](#1-csrf-protection-on-mutation-routes) |
| **Trusted proxy required** | Apps behind load balancers/CDNs will resolve `REMOTE_ADDR` instead of the real client IP | Apps behind Cloudflare, AWS ALB, Nginx reverse proxy | Add `trustedProxies` to `.env` config |

### Medium Impact

| Change | What Breaks | Who's Affected | Fix |
|--------|------------|----------------|-----|
| **ModelTrait method whitelist** | Controller `__call`/`__callStatic` rejects methods not in the whitelist | Controllers calling model methods beyond `get`, `getBy`, `fetchWhere`, `all`, `search`, `count` | Override `$allowedModelMethods` in controller |
| **File upload fail-closed** | Servers without `finfo` extension reject all MIME-validated uploads | Servers missing the `fileinfo` PHP extension | Install `fileinfo` extension |

### Low Impact

| Change | What Breaks | Who's Affected | Fix |
|--------|------------|----------------|-----|
| **Query limit cap (1000)** | Requests with `limit > 1000` are silently capped | Endpoints that intentionally serve large datasets | Override `$maxLimit` in controller |
| **Immutable fields stripped at storage** | Code that intentionally updates immutable fields is silently blocked | Models declaring `$immutableFields` where those fields were updated through back-channel code | Remove field from `$immutableFields` or update through a different mechanism |
| **JSON depth limit (32)** | Payloads nested deeper than 32 levels fail to decode | APIs accepting deeply nested JSON (rare) | Pass custom depth to `JsonFormat::decode($data, 64)` |
| **CORS whitelist** | Cross-origin requests blocked when `allowedOrigins` configured | Only when you configure the whitelist | Ensure all legitimate origins are listed |

---

## Required Configuration

Add these to your `common/Config/.env` (JSON format):

### Trusted Proxies

**Required if your app runs behind a reverse proxy, CDN, or load balancer.**

```json
{
  "trustedProxies": ["10.0.0.0/8", "172.16.0.0/12", "192.168.0.0/16"]
}
```

Common setups:

| Provider | Trusted Proxy IPs |
|----------|-------------------|
| Cloudflare | [Cloudflare IP ranges](https://www.cloudflare.com/ips/) |
| AWS ALB/ELB | VPC CIDR (e.g., `"10.0.0.0/8"`) |
| Nginx on same host | `"127.0.0.1"` |
| Docker bridge | `"172.17.0.0/16"` |
| No proxy (direct) | Omit or `[]` (default — proxy headers ignored) |

### CORS Allowed Origins

**Optional. When omitted, all origins are allowed (development mode).**

```json
{
  "cors": {
    "allowedOrigins": [
      "https://yourdomain.com",
      "https://app.yourdomain.com",
      "https://admin.yourdomain.com"
    ]
  }
}
```

### Full Example

```json
{
  "domain": "production",
  "trustedProxies": ["10.0.0.0/8"],
  "cors": {
    "allowedOrigins": [
      "https://yourdomain.com",
      "https://app.yourdomain.com"
    ]
  },
  "modules": ["User", "Forum"],
  "services": []
}
```

---

## Migration Checklist

Run through this checklist when upgrading:

- [ ] **Update `common/Config/.env`** with `trustedProxies` (if behind a proxy)
- [ ] **Update `common/Config/.env`** with `cors.allowedOrigins` (for production)
- [ ] **Add CSRF token handling** to your frontend (see below)
- [ ] **Verify `fileinfo` PHP extension** is installed (`php -m | grep fileinfo`)
- [ ] **Check controllers** that call model methods via `__call` — expand `$allowedModelMethods` if needed
- [ ] **Check endpoints** that serve more than 1000 records — override `$maxLimit` if needed
- [ ] **Check models** with `$immutableFields` — verify no legitimate code needs to update those fields
- [ ] **Run your test suite** to catch signature issues (policy validation now runs everywhere)
- [ ] **Call `rotate()`** on CSRF gate after login/logout (see below)

---

## Feature Details

### 1. CSRF Protection on Mutation Routes

**What changed:** All POST, PUT, PATCH, and DELETE routes now automatically apply `CrossSiteProtectionMiddleware`. This validates a CSRF token on every mutation request.

**Frontend integration:**

Your frontend must include a CSRF token with every mutation request. The token is available from the CSRF gate:

```php
// Server-side: expose token to frontend
$token = csrf()->getToken();
```

```javascript
// Client-side: include in requests
fetch('/api/resource', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken,  // or 'csrf-token' header
    },
    body: JSON.stringify(data),
});
```

**Opt out for specific routes:**

If a route must be publicly accessible without CSRF (e.g., webhooks, OAuth callbacks), exclude it:

```php
router()
    ->post('webhook/stripe', [WebhookController::class, 'stripe'])
    ->withoutMutationMiddleware();
```

**Opt out for an entire API group:**

If your API uses a different authentication mechanism (e.g., API keys, JWT), you can disable CSRF at the router level before routes are registered, or apply a different middleware set.

---

### 2. ModelTrait Method Whitelist

**What changed:** Controllers using `ModelTrait` can only forward these methods to models via `__call`/`__callStatic`:

- `get`
- `getBy`
- `fetchWhere`
- `all`
- `search`
- `count`

Any other method call returns an error response.

**If your controller needs additional methods:**

```php
class MyController extends ResourceController
{
    // Expand the whitelist for this controller
    protected array $allowedModelMethods = [
        'get',
        'getBy',
        'fetchWhere',
        'all',
        'search',
        'count',
        'myCustomStaticMethod',  // Add your custom method
    ];
}
```

**Best practice:** Prefer calling model methods directly in your controller's public methods instead of relying on `__call` forwarding:

```php
// ✅ Preferred — explicit and clear
public function active(Request $request): object
{
    $result = MyModel::getActiveRecords();
    return $this->response($result);
}

// ⚠️ Works but opaque — relies on __call
// MyController::getActiveRecords() forwarded via ModelTrait
```

---

### 3. Trusted Proxy Configuration

**What changed:** Proxy headers (`X-Forwarded-For`, `X-Real-IP`, `CF-Connecting-IP`, etc.) are **only trusted when `REMOTE_ADDR` matches a configured trusted proxy IP**.

When unconfigured, `PublicIp::get()` returns `REMOTE_ADDR` directly — proxy headers are ignored.

**Header trust priority** (when proxy is trusted):
1. `CF-Connecting-IP` (Cloudflare)
2. `X-Real-IP`
3. `X-Cluster-Client-IP`
4. `X-Forwarded-For` (first IP)
5. `Client-IP`

**CIDR support:** You can specify IP ranges:
```json
"trustedProxies": ["10.0.0.0/8", "172.16.0.0/12", "192.168.0.0/16", "127.0.0.1"]
```

**Impact if not configured:** Any IP-based logic (rate limiting, geolocation, audit logging) will see the proxy's IP instead of the client's real IP.

---

### 4. CORS Origin Whitelist

**What changed:** The `Access-Control-Allow-Origin` header is now validated against a configurable whitelist.

| Configuration State | Behavior |
|---|---|
| `allowedOrigins` **not set** or **empty** | All origins allowed (reflects `Origin` header) — safe for development |
| `allowedOrigins` **configured** | Only listed origins receive the header; others are blocked |

The `csrf-token` header was also added to `Access-Control-Allow-Headers` to support CSRF token delivery via custom headers.

---

### 5. Input Validation Whitelist

**What changed:** The `Validator` class now restricts which sanitization and validation methods can be called dynamically.

**Allowed sanitizers:** `string`, `int`, `float`, `email`, `ip`, `phone`, `mac`, `bool`, `url`, `domain`

**Allowed validators:** `string`, `int`, `float`, `email`, `ip`, `phone`, `mac`, `bool`, `url`, `domain`, `image`

If your application uses custom validation types, you'll need to extend the `Validator` class and expand the constants:

```php
class AppValidator extends Validator
{
    protected const ALLOWED_VALIDATORS = [
        ...parent::ALLOWED_VALIDATORS,
        'myCustomType',
    ];
}
```

---

### 6. File Upload Fail-Closed

**What changed:** `FileValidator::validateFileContent()` now returns `false` when the MIME type cannot be determined (e.g., `finfo` extension missing). Previously it returned `true` (fail-open).

**Action required:** Ensure the `fileinfo` PHP extension is installed:

```bash
# Check if installed
php -m | grep fileinfo

# Install on Ubuntu/Debian
sudo apt install php-fileinfo

# Install on RHEL/CentOS
sudo yum install php-fileinfo

# Already included in most PHP distributions (including XAMPP)
```

---

### 7. Request Body Parsing (Content-Type Enforcement)

**What changed:** `Request::setCustomInputs()` for PUT/PATCH/DELETE only parses the request body into `$_REQUEST` when `Content-Type` is `application/x-www-form-urlencoded`.

JSON bodies (`application/json`) are no longer blindly parsed into `$_REQUEST`. Access JSON data via:

```php
// ✅ Correct — use json() or raw() for JSON bodies
$data = $request->json('fieldName');

// ✅ Or read the full body
$body = JsonFormat::decode(Request::body());
```

**Also fixed:** `Request::json()` now uses `raw()` instead of `input()`, so JSON containing HTML-like content (e.g., `<div>`) is no longer corrupted by `strip_tags()`.

---

### 8. JSON Depth Limit

**What changed:** `JsonFormat::decode()` now enforces a maximum nesting depth of **32 levels** (previously PHP's default of 512).

Payloads nested deeper than 32 levels will return `null` from `decode()`.

**Override when needed:**

```php
// For a specific decode call
$data = JsonFormat::decode($jsonString, 64);  // Allow 64 levels

// The second parameter is the depth limit
```

---

### 9. Immutable Fields at Storage Layer

**What changed:** When a model declares `$immutableFields`, the `Storage::getUpdateData()` method now automatically strips those fields before executing the UPDATE query. This means immutability is enforced at the **storage layer**, not just the controller layer.

Previously, `ResourceController` stripped immutable fields in `modifyUpdateItem()`, but code bypassing the controller (services calling `$model->update()` directly) could still update immutable fields.

**Behavior:**
- Fields listed in `$immutableFields` are converted to `snake_case` and removed from the update data
- This is **in addition to** the controller-level stripping (defense in depth)
- The `id` field is never stripped (it's used as the WHERE clause)

**If you need to update an "immutable" field** (e.g., admin override, data migration):

```php
// Option 1: Remove field from $immutableFields
// Option 2: Use a direct query builder
MyModel::builder()
    ->update()
    ->set(['normally_immutable_field' => $newValue])
    ->where('id = ?')
    ->execute([$id]);
```

---

### 10. CSRF Token Rotation

**What changed:** `CrossSiteRequestForgeryGate` now has a public `rotate()` method that invalidates the current token and generates a new one.

**When to call rotate:**

```php
// After login
public function login(Request $request): object
{
    // ... authenticate user ...
    csrf()->rotate();  // New token after auth state change
    return $this->success($userData);
}

// After logout
public function logout(Request $request): object
{
    csrf()->rotate();  // Invalidate old token
    session_destroy();
    return $this->success(['message' => 'Logged out']);
}

// After privilege escalation (e.g., sudo mode)
public function elevate(Request $request): object
{
    // ... verify password/2FA ...
    csrf()->rotate();
    return $this->success(['elevated' => true]);
}
```

**Frontend impact:** After `rotate()`, the frontend must fetch/receive the new token and use it for subsequent requests. Return it in the response or set it via a cookie.

---

### 11. Query Limit Enforcement

**What changed:** `ApiController::getAllInputs()` now caps the `limit` parameter to a configurable maximum (default: **1000**).

A request for `?limit=99999` will be silently capped to `1000`.

**Override per controller:**

```php
class ReportController extends ResourceController
{
    // Allow larger exports for this controller
    protected int $maxLimit = 5000;
}

class DashboardController extends ResourceController
{
    // Restrict to smaller page sizes
    protected int $maxLimit = 100;
}
```

---

### 12. Error Log Sanitization

**What changed:** Two improvements to prevent sensitive data leakage in error logs:

1. **Backtrace argument removal:** Exception backtraces no longer include function arguments (which could contain passwords, tokens, PII). Uses `DEBUG_BACKTRACE_IGNORE_ARGS`.

2. **Path redaction:** File paths in backtraces are stripped of the `BASE_PATH` prefix, preventing server directory structure disclosure.

3. **Credential masking:** Error messages containing SMTP AUTH credentials or password patterns are automatically masked before storage. For example:
   - `SMTP AUTH failed: user@example.com / secret123` → `SMTP AUTH failed: [CREDENTIALS REDACTED]`

**No action required** — this is automatic and transparent.

---

### 13. Policy Validation in Production

**What changed:** Policy method signature validation now runs in **all environments**, not just development.

If a policy method has an unexpected signature (e.g., `public function get(int $id)` instead of `public function get(Request $request)`), it will:

- **Development:** Trigger a visible `E_USER_WARNING`
- **Production:** Log via `error_log()` (silent to users)

**Valid signatures:**
```php
// ✅ Accepts Request parameter
public function get(Request $request): bool
{
    return $this->ownsResource($request->getInt('id'));
}

// ✅ No parameters
public function all(): bool
{
    return true;
}

// ❌ Wrong — old-style int parameter (will be logged as warning)
public function get(int $id): bool
{
    return auth()->user->isUser($id);
}
```

**Action:** Check your error logs after deployment for messages like:
```
Policy method Modules\User\Auth\Policies\UserPolicy::get() has an unexpected signature...
```

Update any flagged methods to use `Request $request` or zero parameters.

---

### 14. Long-Running Process Support

**What changed:** `Request::reset()` and `PublicIp::reset()` methods clear cached static properties between requests.

**When to use:** If your app runs in a long-running process (Swoole, RoadRunner, ReactPHP, or persistent PHP-FPM workers handling multiple requests), call these between requests:

```php
// In your worker loop or middleware
Request::reset();
PublicIp::reset();
```

**Not needed for:** Standard PHP-FPM (process-per-request) or Apache mod_php setups.

---

## FAQ

### Will this break my existing API?

**Yes, if you don't add CSRF tokens.** The CSRF middleware is the biggest breaking change. If your frontend doesn't send CSRF tokens, all mutation requests (POST/PUT/PATCH/DELETE) will fail.

For APIs consumed by external clients using API keys or JWT, opt out of CSRF middleware on those routes using `withoutMutationMiddleware()`.

### I'm behind Cloudflare — what do I configure?

Add Cloudflare's IP ranges to `trustedProxies`:
```json
{
  "trustedProxies": [
    "173.245.48.0/20", "103.21.244.0/22", "103.22.200.0/22",
    "103.31.4.0/22", "141.101.64.0/18", "108.162.192.0/18",
    "190.93.240.0/20", "188.114.96.0/20", "197.234.240.0/22",
    "198.41.128.0/17", "162.158.0.0/15", "104.16.0.0/13",
    "104.24.0.0/14", "172.64.0.0/13", "131.0.72.0/22"
  ]
}
```

### My tests are failing after the upgrade

Common causes:
1. **Policy signature warnings** — Update policy methods to accept `Request $request` instead of typed IDs
2. **CSRF rejections in HTTP tests** — Mock or disable CSRF middleware in test environment
3. **Model method calls via `__call`** — Add the method to `$allowedModelMethods`

### How do I disable CSRF for my entire app (not recommended)?

Remove or comment out the `defaultMutationMiddleware` call. However, this removes CSRF protection entirely. Prefer opting out per-route instead.

### What's the minimum PHP version?

PHP 8.1+ (unchanged from previous Proto versions).
