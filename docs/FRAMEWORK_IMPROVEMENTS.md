# Proto Framework Improvements

> Summary of framework-level changes implemented to reduce boilerplate, enforce conventions, and let the framework do more heavy lifting.

---

## Table of Contents

1. [Model — Atomic Counter Methods](#1-model--atomic-counter-methods)
2. [Model — Auto-Alias from Table Name](#2-model--auto-alias-from-table-name)
3. [PivotModel — Base Class for Pivot/Junction Tables](#3-pivotmodel--base-class-for-pivotjunction-tables)
4. [ToggleLikeTrait — Standardized Like/Toggle Logic](#4-toggleliketrait--standardized-liketoggle-logic)
5. [TogglePivotTrait — Generic Pivot Toggle](#5-togglepivottrait--generic-pivot-toggle)
6. [VoteableTrait — Shared Vote/Score Service](#6-voteabletrait--shared-votescore-service)
7. [AudienceTargetingTrait — Shared Multi-Dimensional Targeting](#7-audiencetargetingtrait--shared-multi-dimensional-targeting)
8. [SyncController — SSE/Redis Streaming Base Class](#8-synccontroller--sseredis-streaming-base-class)
9. [Policy — Auto-Type Inference & CamelCase Validation](#9-policy--auto-type-inference--camelcase-validation)
10. [Router — Auto-CSRF on Mutation Routes](#10-router--auto-csrf-on-mutation-routes)
11. [Gateway — Module Communication Base Class](#11-gateway--module-communication-base-class)

---

## 1. Model — Atomic Counter Methods

**File**: `src/Models/Model.php`

**Problem**: Counter updates (like likeCount, memberCount) used fetch-modify-write patterns that are race-condition prone under concurrent requests.

**Solution**: Added `atomicIncrement()` and `atomicDecrement()` as static methods on the base Model class. These use SQL `SET field = field + ?` for safe concurrent updates.

### Methods

```php
/**
 * Atomically increment a counter field.
 *
 * @param mixed $id The record's primary key value.
 * @param string $field The field to increment (camelCase model field).
 * @param int $amount The amount to increment by (default 1).
 * @return bool
 */
public static function atomicIncrement(mixed $id, string $field, int $amount = 1): bool

/**
 * Atomically decrement a counter field (floors at zero by default).
 *
 * @param mixed $id The record's primary key value.
 * @param string $field The field to decrement (camelCase model field).
 * @param int $amount The amount to decrement by (default 1).
 * @param bool $floor Whether to floor at zero (default true).
 * @return bool
 */
public static function atomicDecrement(mixed $id, string $field, int $amount = 1, bool $floor = true): bool
```

### Usage

```php
// Before (race-condition prone)
$post = Post::get($postId);
$post->likeCount++;
$post->update();

// After (atomic, safe)
Post::atomicIncrement($postId, 'likeCount');
Post::atomicDecrement($postId, 'likeCount');

// Custom amounts
Post::atomicIncrement($postId, 'viewCount', 5);

// Allow negative values (no floor)
Post::atomicDecrement($postId, 'score', 1, false);
```

---

## 2. Model — Auto-Alias from Table Name

**File**: `src/Models/Model.php`

**Problem**: Models without an explicit `$alias` risked ambiguous-column SQL errors when joins were added later.

**Solution**: The `alias()` method now auto-generates an alias from the table name by taking the first letter of each underscore-separated segment if no explicit alias is set.

### Behavior

```php
// Auto-inferred aliases:
// 'users' → 'u'
// 'car_profiles' → 'cp'
// 'user_vehicle_preferences' → 'uvp'

// Explicit alias still takes priority:
protected static ?string $alias = 'usr'; // This wins over auto-inference
```

### Impact

- No breaking changes — models with explicit `$alias` are unaffected.
- Models that previously had `$alias = null` now get an auto-generated alias.
- The `getAlias()` instance method now delegates to `alias()` for consistency.

---

## 3. PivotModel — Base Class for Pivot/Junction Tables

**File**: `src/Models/PivotModel.php` *(new)*

**Problem**: Pivot/junction table models (likes, bookmarks, follows) are write-once but lacked a consistent base class enforcing immutability.

**Solution**: New `PivotModel` extends `Model` with default `$immutableFields` for pivot records.

### Usage

```php
<?php declare(strict_types=1);

namespace Modules\Post\Models;

use Proto\Models\PivotModel;

class PostLike extends PivotModel
{
    protected static ?string $tableName = 'post_likes';
    protected static array $fields = ['id', 'userId', 'postId', 'createdAt'];
    // $immutableFields already set: ['userId', 'createdAt', 'createdBy']
}
```

---

## 4. ToggleLikeTrait — Standardized Like/Toggle Logic

**File**: `src/Services/Traits/ToggleLikeTrait.php` *(new)*

**Problem**: 4 near-identical like-toggle implementations across services with different quality levels (some using fetch-update, some using atomic SQL).

**Solution**: Trait that handles the entire like flow with atomic counters.

### Usage

```php
<?php declare(strict_types=1);

namespace Modules\Post\Services;

use Proto\Services\Service;
use Proto\Services\Traits\ToggleLikeTrait;
use Modules\Post\Models\Post;
use Modules\Post\Models\PostLike;

class PostMainService extends Service
{
    use ToggleLikeTrait;

    public function togglePostLike(int $userId, int $postId): object
    {
        return $this->toggleLike(
            PostLike::class,
            Post::class,
            $userId,
            $postId,
            'postId',
            'likeCount'
        );
    }
}

// Returns: {liked: bool, likeCount: int}
```

### Method Signature

```php
protected function toggleLike(
    string $likeModelClass,      // The Like pivot model class
    string $parentModelClass,    // The parent model class (has the counter)
    int $userId,                 // The user performing the like
    int $itemId,                // The target item ID
    string $itemIdField,         // FK field on the like model (e.g., 'postId')
    string $counterField         // Counter field on the parent model (e.g., 'likeCount')
): object
```

---

## 5. TogglePivotTrait — Generic Pivot Toggle

**File**: `src/Services/Traits/TogglePivotTrait.php` *(new)*

**Problem**: BookmarkService and FavoriteService had byte-for-byte identical toggle logic.

**Solution**: Trait for toggling pivot record existence (no counter involved).

### Usage

```php
<?php declare(strict_types=1);

namespace Modules\Content\Services;

use Proto\Services\Service;
use Proto\Services\Traits\TogglePivotTrait;
use Modules\Content\Models\Bookmark;

class BookmarkService extends Service
{
    use TogglePivotTrait;

    public function toggle(int $userId, string $itemType, int $itemId): object
    {
        return $this->togglePivot(Bookmark::class, [
            'userId' => $userId,
            'itemType' => $itemType,
            'itemId' => $itemId
        ]);
    }
}

// Returns: {active: bool, record: ?object}
```

---

## 6. VoteableTrait — Shared Vote/Score Service

**File**: `src/Services/Traits/VoteableTrait.php` *(new)*

**Problem**: Two separate vote implementations with different quality levels. One had a bug calling `update()` on a stdClass from `getBy()`.

**Solution**: Trait that handles up/down voting with toggle-off, vote-switching, and atomic score updates.

### Usage

```php
<?php declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Proto\Services\Service;
use Proto\Services\Traits\VoteableTrait;
use Modules\Vehicle\Models\VehicleProblem;
use Modules\Vehicle\Models\VehicleProblemVote;

class VehicleProblemService extends Service
{
    use VoteableTrait;

    public function voteProblem(int $userId, int $problemId, string $direction): object
    {
        return $this->vote(
            VehicleProblemVote::class,
            VehicleProblem::class,
            $userId,
            $problemId,
            'problemId',
            $direction,
            'score'
        );
    }
}

// Returns: {direction: ?string, score: int}
// direction is 'up', 'down', or null (toggled off)
```

### Vote Behavior

| Current State | Action | Result |
|--------------|--------|--------|
| No vote | Vote up | Creates vote (+1), score +1 |
| No vote | Vote down | Creates vote (-1), score -1 |
| Voted up | Vote up | Removes vote (toggle off), score -1 |
| Voted up | Vote down | Switches to down, score -2 (swing) |
| Voted down | Vote down | Removes vote (toggle off), score +1 |
| Voted down | Vote up | Switches to up, score +2 (swing) |

---

## 7. AudienceTargetingTrait — Shared Multi-Dimensional Targeting

**File**: `src/Services/Traits/AudienceTargetingTrait.php` *(new)*

**Problem**: EventAudienceService and GroupAudienceService were structurally identical ~100-line classes differing only in FK names and model classes.

**Solution**: Trait with `getTargeting()` and `saveTargets()` methods. Services define their dimension config via `getTargetingConfig()`.

### Usage

```php
<?php declare(strict_types=1);

namespace Modules\Event\Services;

use Proto\Services\Service;
use Proto\Services\Traits\AudienceTargetingTrait;
use Modules\Event\Models\EventBrandTarget;
use Modules\Event\Models\EventVehicleTypeTarget;
use Modules\Event\Models\EventInterestTarget;

class EventAudienceService extends Service
{
    use AudienceTargetingTrait;

    protected function getTargetingConfig(): array
    {
        return [
            'brands' => ['model' => EventBrandTarget::class, 'fk' => 'eventId'],
            'vehicleTypes' => ['model' => EventVehicleTypeTarget::class, 'fk' => 'eventId'],
            'interests' => ['model' => EventInterestTarget::class, 'fk' => 'eventId', 'valueField' => 'interestId'],
        ];
    }
}

// Usage:
$service = new EventAudienceService();
$targeting = $service->getTargeting($eventId);
// Returns: {brands: [...], vehicleTypes: [...], interests: [...]}

$service->saveTargets($eventId, (object)[
    'brands' => [1, 2, 3],
    'vehicleTypes' => [4, 5],
    'interests' => [10, 11, 12]
]);
```

---

## 8. SyncController — SSE/Redis Streaming Base Class

**File**: `src/Controllers/SyncController.php` *(new)*

**Problem**: 8 controllers duplicated the same SSE/Redis streaming pattern.

**Solution**: Abstract `SyncController` that extends `ApiController`. Subclasses define `getChannel()` and `handleMessage()`.

### Usage

```php
<?php declare(strict_types=1);

namespace Modules\Post\Controllers;

use Proto\Controllers\SyncController;
use Proto\Http\Router\Request;

class PostSyncController extends SyncController
{
    protected function getChannel(Request $request): string|array
    {
        $postId = $request->getInt('postId');
        return "post:{$postId}";
    }

    protected function handleMessage(string $channel, array $message, Request $request): array|null|false
    {
        // Return array to send as SSE event
        return ['merge' => $message, 'deleted' => []];

        // Return null to skip this message
        // Return false to terminate the connection
    }
}

// Route registration:
router()->get('post/:postId/sync', [PostSyncController::class, 'sync']);
```

### Multi-Channel Support

```php
protected function getChannel(Request $request): string|array
{
    $userId = session()->user->id;
    return ["user:{$userId}", "global:notifications"];
}
```

---

## 9. Policy — Auto-Type Inference & CamelCase Validation

**File**: `src/Auth/Policies/Policy.php`

**Problem**: 5 different `$type` naming conventions across 60 policies. Developers had to remember to set `$type` and often used inconsistent formats.

**Solution**:
1. **Auto-inference**: If `$type` is not explicitly set, it's auto-inferred from the class name (`EventPolicy` → `'event'`, `GroupPostPolicy` → `'groupPost'`).
2. **CamelCase validation**: In dev mode, warns if `$type` contains non-camelCase characters (`-`, `.`, `_`).

### Behavior

```php
// Auto-inferred — no need to set $type
class EventPolicy extends Policy
{
    // $type is automatically 'event'
}

class GroupPostPolicy extends Policy
{
    // $type is automatically 'groupPost'
}

// Explicit still works and takes priority
class UserPolicy extends Policy
{
    protected ?string $type = 'user'; // Uses this
}

// Dev-mode warning for non-standard types
class BadPolicy extends Policy
{
    protected ?string $type = 'group-post'; // ⚠️ Warning: use camelCase
}
```

### Impact

- No breaking changes — explicit `$type` declarations still work.
- Policies without `$type` now get auto-inferred values instead of warnings.
- Inconsistent formats trigger dev-mode notices to guide standardization.

---

## 10. Router — Auto-CSRF on Mutation Routes

**File**: `src/Http/Router/Router.php`

**Problem**: 21 API route files had mutation routes without CSRF middleware. Developers had to remember to apply middleware to every route individually.

**Solution**: Added `defaultMutationMiddleware()` to auto-apply middleware on all POST/PUT/PATCH/DELETE routes, with `withoutMutationMiddleware()` for explicit opt-out.

### Setup (in ApiRouter or bootstrap)

```php
use Proto\Http\Middleware\CrossSiteProtectionMiddleware;

$router = router();
$router->defaultMutationMiddleware([CrossSiteProtectionMiddleware::class]);

// All subsequent mutation routes automatically get CSRF protection
$router->resource('user', UserController::class);          // CSRF on add/update/delete
$router->post('post/create', [PostController::class, 'create']); // CSRF auto-applied
$router->get('post/list', [PostController::class, 'list']);       // Not a mutation — skipped
```

### Opt-Out for Special Routes

```php
// Webhooks, OAuth callbacks, etc.
router()->withoutMutationMiddleware()->post('webhook/stripe', [WebhookController::class, 'handle']);
router()->withoutMutationMiddleware()->post('oauth/callback', [OAuthController::class, 'callback']);
```

### How It Works

1. `defaultMutationMiddleware([...])` stores middleware to auto-apply.
2. Every call to `addRoute()` checks if the HTTP method is a mutation (POST, PUT, PATCH, DELETE, or ALL).
3. If mutation + defaults set → middleware is merged with any explicitly provided middleware.
4. `withoutMutationMiddleware()` sets a one-time flag to skip for the next route only.
5. GET and OPTIONS routes are never affected.

---

## 11. Gateway — Module Communication Base Class

**File**: `src/Module/Gateway.php` *(new)*

**Problem**: Empty gateways with no useful methods, missing gateways, and raw service instances returned instead of gateway wrappers.

**Solution**: Abstract `Gateway` class with default CRUD operations. Sub-feature methods layer on top.

### Usage

```php
<?php declare(strict_types=1);

namespace Modules\User\Gateway;

use Proto\Module\Gateway;
use Modules\User\Models\User;

class UserGateway extends Gateway
{
    protected function model(): string
    {
        return User::class;
    }

    // Default CRUD: get(), getBy(), fetchWhere(), create(), remove()
    // are inherited automatically.

    // Add domain-specific methods:
    public function getByEmail(string $email): ?object
    {
        return $this->getBy(['email' => $email]);
    }
}

// Usage from other modules:
$gateway = new UserGateway();
$user = $gateway->get(1);
$user = $gateway->getByEmail('john@example.com');
$newUser = $gateway->create((object)['name' => 'John', 'email' => 'john@example.com']);
```

---

## Files Changed Summary

| File | Status | Description |
|------|--------|-------------|
| `src/Models/Model.php` | Modified | Added `atomicIncrement()`, `atomicDecrement()`, auto-alias in `alias()` |
| `src/Models/PivotModel.php` | **New** | Base class for write-once pivot/junction table models |
| `src/Auth/Policies/Policy.php` | Modified | Auto-type inference via `resolveType()`, camelCase validation |
| `src/Http/Router/Router.php` | Modified | Auto-CSRF via `defaultMutationMiddleware()`, `withoutMutationMiddleware()` |
| `src/Controllers/SyncController.php` | **New** | Base SSE/Redis streaming controller |
| `src/Module/Gateway.php` | **New** | Base class for module gateway CRUD operations |
| `src/Services/Traits/ToggleLikeTrait.php` | **New** | Like/unlike toggle with atomic counters |
| `src/Services/Traits/TogglePivotTrait.php` | **New** | Generic pivot record toggle |
| `src/Services/Traits/VoteableTrait.php` | **New** | Up/down voting with score management |
| `src/Services/Traits/AudienceTargetingTrait.php` | **New** | Multi-dimensional audience targeting |

---

## Migration Notes

All changes are **backwards-compatible**:

- **Model**: New static methods only — no existing behavior changed. Auto-alias only activates when `$alias` is null.
- **Policy**: Auto-inference only when `$type` is null — explicit `$type` declarations still take priority.
- **Router**: Default mutation middleware is empty by default — no middleware is auto-applied until `defaultMutationMiddleware()` is called.
- **New traits**: Opt-in via `use` statement — no existing code is affected.
- **New classes**: Opt-in by extending — no existing code is affected.
