# Copilot Instructions for Proto Framework

**Goal**: Enable AI agents to build resilient, scalable, maintainable, and secure code with minimal errors and without human intervention.

## 1. Project Overview & Architecture

We strive to maintain high code quality and consistency. The code should be resilient, scalable, maintainable, and secure. Functions and methods should adhere to single responsibility principle, and classes should follow SOLID principles. Fail gracefully with proper error handling and logging.

### Overview
- This is the Proto framework (modular monolith for PHP ≥ 8.1). Core lives under `src/` and is installed as a Composer package; apps using Proto keep app code in `modules/` and shared config in `common/Config/.env` (JSON).
- Bootstrapping happens via `Proto\Base` → `Proto\System` + `Config`, then `ModuleManager::activate(env('modules'))` and `ServiceManager::activate(env('services'))`.
- HTTP stack: `src/Http/Router/*` implements a lightweight router with middleware, request/response wrappers, and a resource pattern that maps HTTP verbs to controller methods.
- API front door: `src/Api/ApiRouter.php` sets up global helpers and routes. It dynamically includes `modules/<Module>/Api/**/api.php` via `ResourceHelper` for URLs like `/user/account/...`.

### Autoloading
- PSR-4: `Modules\` → `modules/`, `Common\` → `common/`.
- Migrations: classmapped from `common/Migrations` and `modules/*/Migrations`.

## 2. Code Style & Conventions (CRITICAL)

Always use doc blocks for classes, properties, members, functions, types, and methods.

### Strict Types
**ALWAYS** declare strict types at the top of every PHP file:
```php
<?php declare(strict_types=1);
```

### Braces
**Opening braces ALWAYS on new line** (methods, classes, if/else, loops):
```php
// ✅ CORRECT
public function getUserCars(int $userId): array
{
    return CarProfile::fetchWhere(['userId' => $userId]);
}

if ($condition)
{
    // code
}

// ❌ WRONG
public function getUserCars(int $userId): array {
    return CarProfile::fetchWhere(['userId' => $userId]);
}
```

### References
Use the "use" statement for class references, NOT fully qualified names inline.

```php
// ✅ CORRECT
use Modules\User\Models\User;

$user = User::get($userId);

// ❌ WRONG
$user = \Modules\User\Models\User::get($userId);
```

### Spacing
Use tabs for indentation, 4 spaces for alignment.

**NO blank lines** between variable assignment and immediate condition check:
```php
// ✅ CORRECT
$carProfile = CarProfile::get($carProfileId);
if (!$carProfile)
{
    return false;
}

// ❌ WRONG
$carProfile = CarProfile::get($carProfileId);

if (!$carProfile)
{
    return false;
}
```

## 3. Global Helpers & Core Patterns

### Global Helpers
Defined in `Config.php` and `Api/ApiRouter.php`:
- `env($key)` returns config (from `common/Config/.env`). `envUrl()` gives the base URL. `ENV_URL` is a constant.
- `router()` returns the singleton `Proto\Http\Router\Router` instance; use it to register routes.
- `session()` gets the session; `modules()` accesses gateways registered by modules; `registerModule($key, $factory)` registers gateways.
- `getSession('key')` / `setSession('key', 'value')` for session access.

### Session Access
The api router sets up global session access:
```php
// Get user from session
$user = session()->user ?? null;
$userId = session()->user->id ?? null;

// Or
getSession('user');

// Set session value
setSession('key', 'value');
```

## 4. Modules

**Location**: `modules/YourModule/YourModuleModule.php`

**Structure**:
```php
<?php declare(strict_types=1);

namespace Modules\YourModule;

use Proto\Module\Module;

class YourModuleModule extends Module
{
    public function activate(): void
    {
        // Setup code
    }
}
```

**CRITICAL**:
- Extend `Proto\Module\Module` (singular, NOT `Proto\Modules\Module`)
- Use `activate()` method NOT `boot()`
- Routes automatically loaded from `modules/*/Api/api.php`

### Gateways
After activation, access gateways as `modules()->user()->v1()->createUser($data)` (your app defines these in each module's `Gateway/Gateway.php`).

## 5. Routing

**Location**: `modules/YourModule/Api/api.php`

The Proto ApiRouter will automatically add an `id` parameter at the end of the path for item-specific actions using a resource route.

### Route Patterns
```php
use Modules\User\Controllers\UserController;

// Resource routes (path: /user/:id?)
router()->resource('user', UserController::class);

// Nested resources (path: /user/:userId/address/:id?)
router()->resource('user/:userId/address', AddressController::class);

// Custom routes (use array callable)
router()->get('user/stats', [UserController::class, 'stats']);

// Fluent chaining
router()
    ->get('garage/portfolio', [GarageController::class, 'portfolio'])
    ->post('garage/reorder', [GarageController::class, 'reorder'])
    ->resource('garage', GarageController::class); // Must be last

// Groups
router()->group('auth/crm', function(Router $router)
{
    $router->post('login', [AuthController::class, 'login']);
    $router->post('mfa/verify', [AuthController::class, 'verifyAuthCode']);
});
```

**CRITICAL**:
- Module routes MUST start with module name: `'garage/...'` NOT `'user/:id/garage/...'`
- Controller methods: ALWAYS wrap in array `[Controller::class, 'method']`
- Use fluent interface for chaining
- Resource resolution (`src/Api/ResourceHelper.php`): URL `/user/account/details` maps to file `modules/User/Api/Account/Details/api.php` (PascalCase folders, numeric segments stripped, dots removed)

## 6. Controllers

**Base Classes**:
- `Proto\Controllers\ResourceController` (CRUD)
- `Proto\Controllers\ApiController` (custom endpoints)
- `Proto\Controllers\Controller` (base)

### Basic Controller Example
```php
<?php declare(strict_types=1);

namespace Modules\User\Controllers;

use Proto\Controllers\ResourceController;
use Proto\Http\Router\Request;
use Modules\User\Models\User;

class UserController extends ResourceController
{
    public function __construct(protected ?string $model = User::class)
    {
        parent::__construct();
    }

    protected function validate(): array
    {
        return [
            'name' => 'string:255|required',
            'email' => 'email|required'
        ];
    }
}
```

### Auto-Audit & User Scoping

The framework provides automatic audit field injection and user scoping:

**Auto-Audit Fields**: `ResourceController` automatically injects `createdBy`, `authorId`, `userId` on add and `updatedBy`, `editedBy` on update — if those fields exist in the model's `$fields` array. **You do not need to manually set these in hooks.**

**Immutable Fields**: When a model declares `$immutableFields`, the `ResourceController` automatically strips those fields from update data in `modifyUpdateItem()`. No need for manual `restrictFields()` calls.

**User Scoping**: Set `$scopeToUser = true` on a controller to automatically:
1. Inject the session user's ID into add data via `$userScopeField` (default: `'userId'`)
2. Filter `all()` queries by the session user's ID

```php
// ✅ CORRECT - Zero boilerplate CRUD controller
class PostController extends ResourceController
{
    public function __construct(protected ?string $model = Post::class)
    {
        parent::__construct();
    }

    // Auto-injects userId on add, auto-filters all() by userId
    protected bool $scopeToUser = true;

    // No need for modifyAddItem/modifyUpdateItem/modifyFilter hooks
    // when all you need is audit fields+immutable protection+user scoping!
}
```

```php
// Custom scope field name
class TeamPostController extends ResourceController
{
    protected bool $scopeToUser = true;
    protected string $userScopeField = 'authorId'; // Use authorId instead of userId
}
```

**Override hooks when needed**: If your controller needs additional logic beyond what auto-audit+scoping provides, override `modifyAddItem` / `modifyUpdateItem` and call `parent::modifyAddItem($data, $request)` first:

```php
protected function modifyAddItem(object &$data, Request $request): void
{
    parent::modifyAddItem($data, $request);

    // Additional custom logic
    $data->slug = Strings::slug($data->title);
}
```

### Route Parameter Auto-Injection

Set `$routeParams` to auto-inject route parameters into add data and auto-filter on `all()`. This eliminates the most common `modifyAddItem()`/`modifyFilter()` override pattern:

```php
/**
 * Route parameters to auto-inject on add and auto-filter on all().
 * Keys are the route param name, values control behavior:
 *   - true: required (setError if missing)
 *   - false: optional (apply only if present)
 */
protected array $routeParams = [];
```

```php
// ✅ CORRECT - Zero-override nested resource controller
class ForumPostController extends ResourceController
{
    protected array $routeParams = [
        'forumId' => true,  // Required, auto-injected on add, auto-filtered on all()
    ];

    public function __construct(protected ?string $model = ForumPost::class)
    {
        parent::__construct();
    }

    // No modifyAddItem() or modifyFilter() needed for forumId!
}
```

```php
// Override hooks when extra logic is needed beyond route params
protected function modifyAddItem(object &$data, Request $request): void
{
    parent::modifyAddItem($data, $request); // Handles scopeToUser + routeParams

    // Additional custom logic
    $data->slug = Strings::slug($data->title);
}
```

### Query String Filter Params

Set `$filterParams` for declarative query-string-to-filter mapping. Combined with `$routeParams`, this eliminates 90%+ of `modifyFilter()` overrides:

```php
/**
 * Query string parameters to auto-apply as filter conditions.
 * Maps param name to type ('int', 'string', 'bool').
 */
protected array $filterParams = [];
```

```php
// ✅ CORRECT - Both route and query params auto-applied
class ForumPostController extends ResourceController
{
    protected array $routeParams = ['forumId' => true];  // From URL path
    protected array $filterParams = ['topicId' => 'int']; // From query string

    // Zero modifyFilter() override needed
}
```

### Enrich User Fields

Set `$enrichUserFields` to auto-attach session user fields to add/update responses so the UI can render the author's name/avatar without a refetch:

```php
/**
 * Session user fields to attach to add/update responses.
 */
protected array $enrichUserFields = [];
```

```php
class ForumPostController extends ResourceController
{
    protected array $enrichUserFields = ['firstName', 'lastName', 'image', 'username', 'verified'];

    // After add/update, response automatically includes:
    // { id: 123, firstName: 'John', lastName: 'Doe', image: '...', ... }
}
```

### Service Delegation

When controllers need multi-step business logic (e.g., creating related records, external API calls), delegate to a service class instead of putting that logic in the controller.

**Declare a service class** with `$serviceClass` — the controller auto-instantiates it and delegates `addItem`/`updateItem`/`deleteItem` to the service's `add`/`update`/`delete` methods when they exist. Audit fields (`createdBy`, `userId`, `updatedBy`, etc.) are automatically injected before delegation.

```php
// ✅ CORRECT - Declare service class, auto-instantiated
class GroupPostController extends ResourceController
{
    protected ?string $serviceClass = GroupPostService::class;
    protected ?string $policy = GroupPostPolicy::class;

    public function __construct(protected ?string $model = GroupPost::class)
    {
        parent::__construct();
    }

    // addItem() automatically delegates to $this->service->add($data)
    // updateItem() automatically delegates to $this->service->update($data)
    // deleteItem() automatically delegates to $this->service->delete($data)

    // Custom actions still use $this->service directly
    public function like(Request $request): object
    {
        $id = $this->getResourceId($request);
        $userId = session()->user->id;
        $result = $this->service->toggleLike($id, $userId);
        return $this->serviceResponse($result, 'Failed to toggle like');
    }
}
```

**Service methods** should return `ServiceResult`, `false`, an array/object, or a scalar ID:

```php
use Proto\Services\Service;
use Proto\Services\ServiceResult;

class GroupPostService extends Service
{
    public function add(object $data): ServiceResult
    {
        $post = new GroupPost($data);
        $post->add();
        if (!$post->id)
        {
            return ServiceResult::failure('Failed to create post');
        }

        // Multi-step: create related records, send notifications, etc.
        $this->createDefaultTags($post->id);
        $this->notifyGroupMembers($post);

        return ServiceResult::success(['id' => $post->id]);
    }

    public function update(object $data): ServiceResult
    {
        $post = GroupPost::get($data->id);
        if (!$post)
        {
            return ServiceResult::failure('Post not found');
        }

        // Business logic before update
        $post->merge($data);
        $post->update();

        return ServiceResult::success(['id' => $post->id]);
    }
}
```

**`serviceResponse()`** is available for custom public methods that call the service:
- `ServiceResult` → auto-handles success/error
- `false` → returns the default error message
- `array`/`object` → wraps in success response
- Scalar (e.g., ID) → wraps as `['id' => $result]`

**Custom service instantiation**: Override `initializeService()` if the service needs constructor arguments:

```php
protected function initializeService(): void
{
    $this->service = new GroupPostService($this->someDependency);
}
```

**CRITICAL**:
- Service `add`/`update`/`delete` methods receive data with audit fields already injected
- Only define the service methods you need — missing methods fall back to default model behavior
- Use `$serviceClass` property (NOT manual `$this->service = new ...()` in constructor)

### Request Handling
Controllers receive `Proto\Http\Router\Request` objects in public methods and hook methods.

**Access Route Parameters**:
Use Request object methods:
- `input($key)` - Get string parameter
- `getInt($key)` - Get integer parameter
- `getBool($key)` - Get boolean parameter
- `json($key)` - Get JSON parameter
- `raw($key)` - Get raw parameter
- `params()` - Get route parameters as object

```php
// ✅ CORRECT
$communityId = $request->getInt('communityId');
$name = $request->input('name');
$isActive = $request->getBool('active');

// Params in URL
$params = $request->params();
$communityId = (int)($params->communityId ?? 0);

// ❌ WRONG - route() doesn't exist
$communityId = $request->route('communityId');
```

### Error Handling
Do not throw exceptions in controllers. Use `$this->setError('message')` in hook methods or `$this->error('message')` in public methods to fail gracefully.

```php
// ✅ CORRECT - Graceful error handling in hook
protected function modifyUpdateItem(object &$data, Request $request): void
{
    $post = GroupPost::get($data->id);
    if (!$post)
    {
        $this->setError('Post not found');
        return;
    }

    $userId = session()->user->id;
    if ($post->userId !== (int)$userId)
    {
        $this->setError('Unauthorized');
        return;
    }
}

// ✅ CORRECT - Graceful error handling in public method
public function customAction(Request $request): object
{
    $id = $request->getInt('id');
    if (!$id)
    {
        return $this->error('ID required');
    }
    // ...
}

// ❌ WRONG - Throwing exceptions
protected function modifyUpdateItem(object &$data, Request $request): void
{
    if (!$post)
    {
        throw new \Exception('Post not found');
    }
}
```

### Authentication Pattern
**CRITICAL**:
- **Policies handle authentication** - Use `protected ?string $policy = YourPolicy::class;` in controllers
- **Controllers assume user authenticated** - After policy check, `session()->user->id` is available
- **DO NOT check auth in controllers** - No `if (!$userId)` checks needed
- **Use session data directly** - `$userId = session()->user->id;` (no null check)

```php
// ✅ CORRECT - Policy enforces auth, controller uses session
class GroupController extends ResourceController
{
    protected ?string $policy = GroupPolicy::class;

    public function join(Request $request): object
    {
        $groupId = $request->getInt('groupId');
        $userId = session()->user->id; // Safe after policy check

        return $this->service->joinGroup($userId, $groupId);
    }
}

// ❌ WRONG - Don't check auth in controller
public function join(Request $request): object
{
    $userId = session()->user->id ?? null;
    if (!$userId)
    {
        return $this->error('User not authenticated');
    }
    // ...
}
```

### ResourceController Request Patterns

**CRITICAL: Method Signatures**

**Public Methods** (receive `Request $request` parameter):
- `add(Request $request)` → calls `addItem(object $data)`
- `update(Request $request)` → calls `updateItem(object $data)`
- `delete(Request $request)` → calls `deleteItem(object $data)`
- `get(Request $request)`, `all(Request $request)`, `search(Request $request)`

The `all()` method is used to list multiple items with optional filtering, sorting, and pagination. It accepts a filter object, optional offset and limit or last cursor and since for pagination, dates object, and optional modifiers for sorting, grouping, searching, and cursor.

```php
public function all(Request $request): object
{
    $inputs = $this->getAllInputs($request);
    $result = $this->model::all($inputs->filter, $inputs->offset, $inputs->limit, $inputs->modifiers);
    return $this->response($result);
}
```

**Protected Methods** (NO Request parameter):
- `addItem(object $data)` - performs the actual add operation
- `updateItem(object $data)` - performs the actual update operation
- `deleteItem(object $data)` - performs the actual delete operation

**Hook Methods** (receive `Request $request` parameter):
- `modifyAddItem(object &$data, Request $request)` - called BEFORE `addItem()`, modifies data by reference
- `modifyUpdateItem(object &$data, Request $request)` - called BEFORE `updateItem()`, modifies data by reference
- `modifyFilter(?object $filter, Request $request)` - called in `all()` to customize filter

### Hook Method Examples

```php
// Add route parameter to data
protected function modifyAddItem(object &$data, Request $request): void
{
    $clientId = $request->getInt('clientId');
    if ($clientId)
    {
        $data->clientId = $clientId;
    }

    // Sanitize content
    if (isset($data->content))
    {
        $data->content = trim(html_entity_decode($data->content, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}

// Restrict fields that shouldn't be modified
protected function modifyUpdateItem(object &$data, Request $request): void
{
    $id = $data->id ?? null;
    $restrictedFields = ['id', 'clientId', 'createdAt', 'createdBy'];
    $this->restrictFields($data, $restrictedFields);
    $data->id = $id; // Restore ID after restriction
}

// Modify filter for all() queries
protected function modifyFilter(?object $filter, Request $request): ?object
{
    $clientId = $request->getInt('clientId');
    if ($clientId)
    {
        $filter->clientId = (int)$clientId;
    }
    return $filter;
}
```

### When to Use Each Pattern

1. **Use hook methods** (`modifyAddItem`, `modifyUpdateItem`) when:
   - Injecting route parameters into data
   - Sanitizing/transforming input data
   - Setting default values
   - Restricting fields

2. **Override public methods** (`add`, `update`) when:
   - Need complex validation based on route parameters
   - Need to call services with custom logic
   - Need to return custom responses
   - Need multiple DB operations

3. **Override protected methods** (`addItem`, `updateItem`) when:
   - Customizing the persistence logic itself
   - Adding post-persistence operations
   - NOT for accessing request data (use hooks instead)

### File Upload Helpers

ResourceController provides built-in helpers for single and batch file uploads:

```php
// Single file upload — returns new filename or null
$filename = $this->handleFileUpload($request, 'avatar', 'local', 'avatars', 'image:2048|mimes:jpeg,png');
if ($filename)
{
    $data->avatar = $filename;
}

// Batch file upload — returns array of metadata objects
$media = $this->handleMediaUpload($request, 'media', 'local', 'forum', 'image:5120');
if (!empty($media))
{
    $data->media = json_encode($media);
}
// Each item: { fileName, originalName, mimeType, size }
```

### Response Methods
Return structured objects using:
- `$this->success($data, $code)` - Success response
- `$this->error($message, $code)` - Error response
- `$this->response($result, $errorMessage)` - Unified output

The router translates these into JSON with status codes.

**CRITICAL**:
- Controllers NEVER access storage classes directly
- Always use model methods: `$car = CarProfile::get($id)` NOT `$storage->get($id)`
- Use validation: `$this->validateRules($data, [...])` or `$request->validate([...])`

## 7. Models

**Base**: `Proto\Models\Model`

### Static Methods (operate on class)
- `create((object)$data)` - returns BOOL
- `get($id)` - returns object|null
- `getWithoutJoins($id)` - returns object|null without eager joins (transaction-safe)
- `fetchWhereWithoutJoins([...])` - returns array without eager joins (transaction-safe)
- `remove($id)` - returns bool
- `fetchWhere([...])` - returns array
- `getBy([...])` - returns object|null

### Instance Methods (operate on object)
- `add()` - persists new instance
- `update()` - updates existing
- `delete()` - removes instance

### Model Configuration

**CRITICAL**: Always add `@property` PHPDoc annotations for every database field on model classes.
This enables IDE autocompletion, eliminates "undefined property" warnings, and enables proper type checking.
The base `Model` class declares `@property int|null $id` — child models inherit it automatically.
Add `@SuppressWarnings PHP0413` to the model docblock if your IDE shows constructor warnings.

```php
<?php declare(strict_types=1);

namespace Modules\User\Models;

use Proto\Models\Model;

/**
 * User Model
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $status
 */
class User extends Model
{
    protected static ?string $tableName = 'users';
    protected static array $fields = ['id', 'name', 'email', 'status'];
    protected static array $fieldsBlacklist = ['password']; // Exclude from JSON output
    protected static string $idKeyName = 'id'; // Default, only set if different

    // Fields that cannot be modified after creation
    // ResourceController auto-strips these on update
    protected static array $immutableFields = ['userId', 'createdAt', 'createdBy'];

    // Pre-persist hook - sanitize/transform before save
    protected static function augment(mixed $data = null): mixed
    {
        if ($data && isset($data->email))
        {
            $data->email = strtolower(trim($data->email));
        }
        return $data;
    }

    // Post-fetch hook - shape API output
    protected static function format(?object $data): ?object
    {
        if ($data)
        {
            $data->displayName = $data->firstName . ' ' . $data->lastName;
        }
        return $data;
    }

    // Eager loading (query-time joins)
    protected static function joins(object $builder): void
    {
        $builder->belongsTo(Organization::class, fields: ['name', 'slug']);
    }
}
```

### Relationships (lazy-loaded)
The lazy joins return a relation object from `Proto\Models\Relations`. The types are: `Relations\HasMany`, `Relations\HasOne`, `Relations\BelongsTo`, `Relations\BelongsToMany`.

```php
// In model methods
$user->hasMany(Post::class); // User has many posts
$user->hasOne(Profile::class); // User has one profile
$post->belongsTo(User::class); // Post belongs to user
$user->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id'); // Many-to-many
```

### CRITICAL Model Patterns
- `create()` takes OBJECT not array: `User::create((object)['name' => 'John'])`
- `create()` returns BOOL not object. Use instance approach to track:
  ```php
  $user = new User();
  $user->name = 'John';
  $user->add(); // now $user->id is available
  ```
- Use constructor with object for efficiency:
  ```php
  // ✅ CORRECT
  $user = new User((object)$data);
  $user->add();

  // ✅ CORRECT - Direct object
  $user = new User((object)[
        'name' => 'John',
        'email' => 'john@example.com'
  ]);
  $user->add();

  // ❌ WRONG - Verbose and unnecessary
  $user = new User();
  foreach ($data as $key => $value)
  {
      $user->$key = $value;
  }
  $user->add();
  ```
- `delete()` is instance method NOT static:
  - ❌ WRONG: `User::delete(5)`
  - ✅ CORRECT: `User::remove(5)` or `$user = User::get(5); $user->delete();`
- In `belongsTo` joins: use named parameters, exclude 'id' field

## 8. Storage

**Base**: `Proto\Storage\Storage`

**Create ONLY if custom queries needed**. Otherwise use model methods.

### Filter Arrays
Storage methods can use filter arrays if set. This is an array that can add a clause to the storage query.

Filter keys in the array can be ambiguous and might need to be prefixed with the table alias if the model is joining tables using eager joins.

```php
$filter = [
    "id = '1'", // ambiguous
    "a.id = '1'", // raw condition with table alias
    ["a.created_at BETWEEN ? AND ?", ['2021-02-02', '2021-02-28']], // Manual bind
    ['a.id', $user->id], // auto bind
    ['a.id', '>', $user->id] // auto bind with operator
];

// IN / NOT IN with arrays (auto-generates placeholders)
$filter = [
    ['userId', 'IN', [1, 2, 3]],          // user_id IN (?, ?, ?)
    ['status', 'NOT IN', ['banned', 'deleted']], // status NOT IN (?, ?)
    ['a.replyId', 'IN', $replyIds],        // works with table aliases
];

$row = User::getBy($filter);   // one
$rows = User::fetchWhere($filter);   // many
```

### Query Builder
```php
<?php declare(strict_types=1);

namespace Modules\User\Storage;

use Proto\Storage\Storage;

class UserStorage extends Storage
{
    public function getActiveUsers(int $limit = 10): array
    {
        return $this->table()
            ->select()
            ->where('status = ?', 'deleted_at IS NULL')
            ->orderBy('created_at DESC')
            ->limit($limit)
            ->fetch(['active']);
    }

    // Conditional where clauses
    public function getRecords(int $id, ?string $type = null): array
    {
        $sql = $this->table()
            ->select()
            ->where('parent_id = ?', 'deleted_at IS NULL');

        $params = [$id];
        if ($type)
        {
            $sql->where('type = ?');
            $params[] = $type;
        }

        return $sql->fetch($params);
    }

    // Update with builder
    public function updateStatus(int $id, string $status): bool
    {
        return $this->table()
            ->update()
            ->set(['status' => $status, 'updated_at' => 'NOW()'])
            ->where('id = ?')
            ->execute([$id]);
    }
}
```

### Ad-hoc Queries
When custom storage not needed:
```php
// In Model static methods
public static function getActiveUsers(): array
{
    return static::builder()
        ->select()
        ->where('status = ?', 'deleted_at IS NULL')
        ->fetch(['active']);
}

// Using closures (compact syntax)
$users = User::storage()->findAll(
    fn($sql, &$p) => (
        $p[] = 'active',
        $sql->where('status = ?')->orderBy('created_at DESC')
    )
);
```

### CRITICAL Storage Patterns
- DO NOT specify `$tableName` or `$connection` (unless non-default DB)
- Use builder's `fetch()` directly: `->fetch($params)` NOT `$this->fetch($sql, $params)`
- Chain multiple where conditions in single call
- Use `first()` not `fetchOne()`
- ALWAYS use builder methods, NEVER raw SQL with table names
- NO `getTableName()` method exists - always use builder

## 9. Migrations

**Location**: `common/Migrations` or `modules/*/Migrations`

### Basic Structure
```php
<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->create('users', function($table)
        {
            $table->id();
            $table->uuid();
            $table->varchar('name', 100);
            $table->varchar('email', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->drop('users');
    }
}
```

### Field Types
- Primary key: `$table->id();`
- UUID: `$table->uuid();`
- Integer: `$table->integer('field', length);`
- String: `$table->varchar('field', length);`
- Text: `$table->text('field')->nullable();`
- Decimal: `$table->decimal('amount', precision, scale);`
- Date: `$table->date('field');`
- Timestamp: `$table->timestamp('field');`
- Boolean: `$table->tinyInteger('field')->default(0);`
- Enum: `$table->enum('field', 'val1', 'val2')->default("'val1'");`

### Audit Fields
- `$table->timestamps();` - created_at, updated_at
- `$table->createdAt();` - created_at only
- `$table->updatedAt();` - updated_at only
- `$table->deletedAt();` - soft delete

### Indexes
- Single: `$table->index('idx_name')->fields('field');`
- Multiple: `$table->index('idx_name')->fields('field1', 'field2');`
- Unique: `$table->unique('unq_name')->fields('field1');`

### Foreign Keys
```php
$table->foreign('user_id')
    ->references('id')
    ->on('users')
    ->onDelete('CASCADE');
```

### Complete Migration Example
```php
<?php declare(strict_types=1);

use Proto\Database\Migrations\Migration;

class CarMaintenanceRecord extends Migration
{
    public function up(): void
    {
        $this->create('car_maintenance_records', function($table)
        {
            // Primary key
            $table->id();
            $table->uuid();

            // Foreign keys
            $table->integer('car_profile_id', 30);
            $table->integer('user_id', 30);

            // Fields
            $table->varchar('title', 200);
            $table->text('description')->nullable();
            $table->enum('type', 'routine', 'repair', 'inspection')->default("'routine'");
            $table->date('service_date');
            $table->decimal('cost', 10, 2)->nullable();

            // Audit fields
            $table->createdAt();
            $table->integer('created_by', 30)->nullable();
            $table->updatedAt();
            $table->integer('updated_by', 30)->nullable();
            $table->deletedAt();

            // Indexes
            $table->index('car_profile_idx')->fields('car_profile_id', 'service_date');
            $table->index('user_idx')->fields('user_id');

            // Foreign keys
            $table->foreign('car_profile_id')->references('id')->on('car_profiles')->onDelete('CASCADE');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
        });
    }

    public function down(): void
    {
        $this->drop('car_maintenance_records');
    }
}
```

### CRITICAL Migration Patterns
- Extend `Proto\Database\Migrations\Migration`
- Use `up()` and `down()` NOT `run()` and `revert()`
- Use `foreign()` NOT `foreignKey()` or `foreignId()`
- DO NOT specify `$connection` unless non-default DB

## 10. Services

**Base**: `Common\Services\Service`

**Pattern**: Services coordinate business logic, models handle data access.

### Single Responsibility Principle
- Methods should do ONE thing well
- Break complex methods into smaller, focused helper methods
- Each method should have a clear, single purpose
- Avoid methods that mix validation, business logic, and persistence

### Succinct Model Instantiation
Always use constructor with object for efficiency:

```php
// ✅ CORRECT - Succinct
$member = new GroupMember((object)[
    'groupId' => $groupId,
    'userId' => $userId,
    'role' => $role
]);
$member->add();

// ✅ CORRECT - With conditional data
$memberData = [
    'groupId' => $groupId,
    'userId' => $userId,
    'role' => $role
];
if ($invitedBy)
{
    $memberData['invitedBy'] = $invitedBy;
}
$member = new GroupMember((object)$memberData);
$member->add();

// ❌ WRONG - Verbose
$member = new GroupMember();
$member->groupId = $groupId;
$member->userId = $userId;
$member->role = $role;
$member->add();
```

### Refactoring Long Methods
```php
// ❌ WRONG - Method doing too much
public function createGroup(int $userId, array $data): object|false
{
    // Validation
    $existing = Group::getBy(['slug' => $data['slug']]);
    if ($existing) return false;

    // Stripe setup
    if (!empty($data['requiresFee']))
    {
        $stripeProduct = $this->createStripeProduct(...);
        $stripePrice = $this->createStripePrice(...);
        $data['stripeProductId'] = $stripeProduct->id;
    }

    // Create group
    $group = new Group((object)$data);
    $group->add();

    // Add member
    $this->addGroupMember($group->id, $userId);

    // Update counts
    $community = Community::get($communityId);
    $community->groupCount++;
    $community->update();

    return $group;
}

// ✅ CORRECT - Delegated to focused methods
public function createGroup(int $userId, int $communityId, array $data): object|false
{
    if (!$this->isGroupSlugUnique($communityId, $data['slug']))
    {
        return false;
    }

    $stripeData = $this->setupGroupStripeIntegration($data);
    if ($stripeData === false)
    {
        return false;
    }

    $group = $this->createGroupRecord($userId, $communityId, array_merge($data, $stripeData));
    if (!$group)
    {
        return false;
    }

    $this->addGroupMember($group->id, $userId, 'owner', null);
    $this->incrementCommunityGroupCount($communityId);

    return Group::get($group->id);
}

protected function isGroupSlugUnique(int $communityId, string $slug): bool
{
    $existing = Group::getBy(['communityId' => $communityId, 'slug' => $slug]);
    return $existing === null;
}

protected function setupGroupStripeIntegration(array $data): array|false
{
    if (empty($data['requiresFee'])) return [];
    // ... focused Stripe setup logic
}

protected function createGroupRecord(int $userId, int $communityId, array $data): ?Group
{
    $group = new Group((object)array_merge($data, [
        'communityId' => $communityId,
        'createdBy' => $userId,
        'memberCount' => 1
    ]));
    $group->add();
    return $group->id ? $group : null;
}

protected function incrementCommunityGroupCount(int $communityId): void
{
    $community = Community::get($communityId);
    if ($community)
    {
        $community->groupCount++;
        $community->update();
    }
}
```

### Location / Proximity Filtering (LocationFilterTrait)

**Location**: `Proto\Services\Traits\LocationFilterTrait`

Use this trait in services that need to filter records by geographic proximity. It builds `ST_Distance_Sphere` conditions compatible with Proto's filter array pattern so callers never write raw SQL distance expressions.

```php
use Proto\Services\Traits\LocationFilterTrait;

class VehicleService extends Service
{
    use LocationFilterTrait;

    // Direct filter on the queried table's POINT column
    public function addLocationFilter(float $lat, float $lon, array &$filter): void
    {
        $this->filterByProximity($filter, [
            'latitude' => $lat,
            'longitude' => $lon,
            'radius' => 50,          // miles (default)
            'alias' => 'v',          // table alias
            'column' => 'position',  // POINT column (default)
        ]);
    }

    // Subquery filter against a related table
    public function addUserLocationFilter(int $userId, array &$filter): void
    {
        $userLocation = UserLocationPreference::getBy(['userId' => $userId]);
        if (!$userLocation || empty($userLocation->longitude) || empty($userLocation->latitude))
        {
            return;
        }

        $this->filterByProximitySubquery($filter, [
            'latitude' => $userLocation->latitude,
            'longitude' => $userLocation->longitude,
            'radius' => $userLocation->radiusMiles ?? 50,
            'table' => 'user_location_preferences',
            'joinColumn' => 'user_id',
            'parentColumn' => 'v.user_id',
        ]);
    }
}
```

**Available methods**:
- `filterByProximity(array &$filter, array $options)` — appends a direct proximity condition on a POINT column
- `filterByProximitySubquery(array &$filter, array $options)` — appends an EXISTS subquery proximity condition against a related table
- `buildProximityCondition(array $options)` — returns a standalone `[sql, params]` condition without appending
- `buildProximitySubqueryCondition(array $options)` — returns a standalone subquery condition
- `convertToMeters(float|int $radius, string $unit)` — utility to convert radius to meters (`'miles'` or `'km'`)

**Options**: `latitude`, `longitude`, `radius` (default 50), `unit` (`'miles'`|`'km'`), `column` (default `'position'`), `alias` (table alias for direct), `table`/`joinColumn`/`parentColumn`/`tableAlias` (for subquery variant).

### CRITICAL Service Patterns
- Services NEVER instantiate storage classes directly
- ❌ WRONG: `$storage = new UserStorage(); $storage->getUsers();`
- ✅ CORRECT: `User::fetchWhere([...])`
- For complex queries, add static methods to model that delegate to storage
- Extract repeated logic into focused helper methods
- Helper methods should be protected unless needed elsewhere

## 11. Validation

**Format**: `'type[:max]|required'`

**Types**: `int`, `float`, `string`, `email`, `ip`, `phone`, `mac`, `bool`, `url`, `domain`

**Examples**:
- `'string:255|required'`
- `'email|required'`
- `'int|required'`

## 12. Auth & Policies

### Gates (Authentication helpers)
```php
// Create in Common/Auth extending Proto\Auth\Gates\Gate
<?php declare(strict_types=1);

namespace Common\Auth;

use Proto\Auth\Gates\Gate;

class UserGate extends Gate
{
    public function isUser(int $userId): bool
    {
        $sessionUserId = $this->session->get('user')->id ?? null;
        return $sessionUserId === $userId;
    }

    public function isAdmin(): bool
    {
        return $this->session->get('role') === 'admin';
    }
}

// Register globally
$auth = auth();
$auth->user = new UserGate();
$auth->user->isUser(1); // Use anywhere
```

### Policies (Authorization)
The Common policy adds a type property to identify the policy type and uses a request method to check if the user is authorized to perform the action by the type and request action method.

Module policies extend the Common policy and define per-action methods as needed.

```php
// Create in Modules/ModuleName/Auth/Policies extending Common\Auth\Policies\Policy
<?php declare(strict_types=1);

namespace Modules\User\Auth\Policies;

use Common\Auth\Policies\Policy;

class UserPolicy extends Policy
{
    /**
     * The type of the policy.
     *
     * @var string|null
     */
    protected ?string $type = 'user';

    // Runs before all methods
    protected function before(): bool
    {
        return (auth()->user->isAdmin());
    }

    // Override this to add a default policy for all actions if no per-action method exists
    protected function default(): bool
    {
        return false;
    }

    // Per-action methods
    public function get(int $id): bool
    {
        return auth()->user->isUser($id);
    }

    // After method hook example
    public function afterGet(mixed $result): bool
    {
        // Check the result object if needed
        $userId = session()->user->id ?? null;
        if (!$userId)
        {
            return false;
        }

        return ($result->id === $userId);
    }

    public function update(int $id): bool
    {
        return auth()->user->isUser($id);
    }
}
```

### Policy Ownership Helpers

The base `Proto\Auth\Policies\Policy` provides built-in helpers for common ownership checks:

```php
// getUserId() - Get the session user's ID
$userId = $this->getUserId(); // Returns ?int

// ownsResource() - Check if resource belongs to session user
public function get(Request $request): bool
{
    $post = Post::get($request->getInt('id'));
    return $this->ownsResource($post->userId ?? null);
}

// matchesRouteUser() - Check if route userId matches session user
public function all(Request $request): bool
{
    return $this->matchesRouteUser($request); // Checks 'userId' param by default
    // or: $this->matchesRouteUser($request, 'authorId'); // Custom param name
}

// Apply to controller
class UserController extends ResourceController
{
    protected ?string $policy = UserPolicy::class;
}

// Routes with dynamic params
router()->resource('user/:userId/account', UserController::class);
```

## 13. Testing

The Proto test framework extends PHPUnit and provides helpers for database assertions and test setup. There are a few traits applied to the base Test class that provide common functionality. Check the Proto composer module `src\Tests\Test` class for details.

### Test Structure
```php
<?php declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Proto\Tests\Test;
use Modules\User\Models\User;

class UserTest extends Test
{
    public function testCreateUser(): void
    {
        $user = new User();
        $user->name = 'John';
        $user->email = 'john@example.com';
        $user->add();

        $this->assertDatabaseHas('users', [
            'name' => 'John',
            'email' => 'john@example.com'
        ]);
    }
}
```

### Test Helpers

**Creating Test Data**:
```php
// Use factories for model creation
protected function createUser(): User
{
    return User::factory()->create([
        'username' => 'testuser' . uniqid(),
        'email' => 'test' . uniqid() . '@example.com'
    ]);
}

// Or manual instantiation (ensure all required fields)
protected function createUser(): User
{
    $user = new User((object)[
        'username' => 'testuser' . uniqid(),
        'email' => 'test' . uniqid() . '@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'firstName' => 'Test',
        'lastName' => 'User',
        'status' => 'offline'
    ]);
    $user->add();

    if (!$user->id)
    {
        throw new \Exception('Failed to create test user');
    }

    return $user;
}
```

### Factories
**Location**: `modules/*/Factories` or `common/Factories`
**Purpose**: Generate test data for models using the Proto Simple Faker class. Check Proto composer package in `\src\Tests\SimpleFaker.php` for available methods.

**Usage**:
```php
// Create and persist
$user = User::factory()->create();

// Create without persisting
$user = User::factory()->make();

// Create multiple
$users = User::factory()->count(5)->create();

// With custom attributes
$user = User::factory()->create(['email' => 'test@example.com']);

// States for variations
$admin = User::factory()->admin()->create();
```

**Factory Structure**:
```php
<?php declare(strict_types=1);

namespace Modules\User\Factories;

use Proto\Models\Factory;
use Modules\User\Models\User;

class UserFactory extends Factory
{
    protected static ?string $model = User::class;

    protected function definition(): array
    {
        return [
            'username' => $this->faker->userName(),
            'email' => $this->faker->email(),
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'firstName' => $this->faker->firstName(),
            'lastName' => $this->faker->lastName()
        ];
    }

    // State methods
    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }
}
```

### Seeders
**Location**: `modules/*/Seeders` or `common/Seeders`
**Purpose**: Populate database with initial/test data
**Run**: `php vendor/bin/phpunit --filter SeederTest` or programmatically

**Usage**:
```php
// In tests or setup scripts
$seeder = new UserSeeder();
$seeder->run();

// Via SeederManager
SeederManager::run([UserSeeder::class, GroupSeeder::class]);
```

**Seeder Structure**:
```php
<?php declare(strict_types=1);

namespace Modules\User\Seeders;

use Proto\Database\Seeders\Seeder;
use Modules\User\Factories\UserFactory;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Using factories
        User::factory()->count(10)->create();

        // Or direct creation
        User::create((object)[
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT)
        ]);
    }
}
```

### Assertions
```php
$this->assertDatabaseHas('table', [...]);
$this->assertDatabaseMissing('table', [...]);
$this->assertDatabaseCount('table', 5);
$this->assertTrue($condition);
$this->assertEquals($expected, $actual);
$this->assertNotNull($value);
$this->assertIsArray($value);
```

### Transaction Limitations

**CRITICAL**:
- Tests auto-wrap in transactions (rollback automatically)
- `Model::get($id)` and `Model::getBy([...])` may return null for data created in same transaction
- Use `Model::fetchWhere([...])` and convert to model: `new Model($data)` for transaction-safe queries although we have pushed updates to handle this better, if you have issues still use this pattern.
- Prefer `assertDatabaseHas()` over re-fetching models when verifying data
- Don't disable foreign key checks
- Don't call custom static methods in tests (may create new connections)
- Use `Model::getWithoutJoins($id)` or `Model::fetchWhereWithoutJoins([...])` to bypass eager joins that may fail in transactions

**Example Transaction-Safe Pattern**:
```php
// ✅ CORRECT - Direct assertion
$user = User::factory()->create();
$this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $user->email]);

// ✅ CORRECT - Use returned object
$user = User::factory()->create();
$this->assertEquals('test@example.com', $user->email);

// ⚠️ MAY FAIL - Re-fetching in transaction
$user = User::factory()->create();
$fetched = User::get($user->id); // May return null
$this->assertNotNull($fetched); // May fail

// ✅ CORRECT - fetchWhere alternative
$users = User::fetchWhere(['id' => $user->id]);
if (!empty($users))
{
    $fetched = new User($users[0]);
}
```

## 14. File Storage (Vault)

**Location**: `Proto\Utils\Files\Vault`
**Config**: `common/Config/.env` under `"files"` key
**Purpose**: Handle file uploads, storage, retrieval, and deletion
**Drivers**: `local`, `s3`

### Configuration Example
```json
"files": {
    "local": {
        "path": "/common/files/",
        "attachments": {
            "path": "/common/files/attachments/"
        }
    },
    "amazon": {
        "s3": {
            "bucket": {
                "uploads": {
                    "secure": true,
                    "name": "main",
                    "path": "main/",
                    "region": "",
                    "version": "latest"
                }
            }
        }
    }
}
```

### File Upload Handling

**CRITICAL**: Use Request methods to access uploaded files, NOT `$_FILES` directly.

**Accessing Files**:
```php
// Single file upload
$avatar = $request->file('avatar'); // Returns UploadFile|null

// Multiple files (array upload like attachments[])
$attachments = $request->fileArray('attachments'); // Returns UploadFile[]

// All uploaded files
$allFiles = $request->files(); // Returns array keyed by input name
```

**Validation with UploadFile Objects**:
```php
// In controller - validate before storing
public function upload(Request $request): object
{
    $data = [
        'name' => $request->input('name'),
        'avatar' => $request->file('avatar') // UploadFile object
    ];

    $rules = [
        'name' => 'string:100|required',
        'avatar' => 'image:2048|required|mimes:jpeg,png'
    ];

    $this->validateRules($data, $rules);

    // Store validated file
    $data['avatar']->store('local', 'avatars');
    return $this->success(['filename' => $data['avatar']->getNewName()]);
}

// With file arrays
public function uploadMultiple(Request $request): object
{
    $attachments = $request->fileArray('attachments');

    foreach ($attachments as $file)
    {
        // Validate each file
        $validator = Validator::create(['file' => $file], [
            'file' => 'image:5120|mimes:jpeg,png,gif'
        ]);

        if ($validator->isValid())
        {
            $file->store('local', 'attachments');
        }
    }

    return $this->success(['count' => count($attachments)]);
}
```

**Request File Validation Methods**:
```php
// Validate single file with rules
$avatar = $request->validateFile('avatar', [
    'avatar' => 'image:2048|required|mimes:jpeg,png'
]);

// Validate file array
$attachments = $request->validateFileArray('attachments', [
    'attachments' => 'file:5120|mimes:pdf,doc,docx'
]);
```

### Storage Operations
```php
use Proto\Utils\Files\Vault;
use Proto\Http\UploadFile;

// In controller - store uploaded file
$uploadFile = $request->file('upload');
$uploadFile->store('local', 'attachments');

// Get file metadata
$originalName = $uploadFile->getOriginalName();
$newName = $uploadFile->getNewName();
$size = $uploadFile->getSize();
$mimeType = $uploadFile->getMimeType();

// Image-specific methods
if ($uploadFile->isImageFile())
{
    [$width, $height] = $uploadFile->getDimensions();
}

// Via Vault directly
Vault::disk('local', 'attachments')->add('/tmp/file.txt');

// Download file
Vault::disk('local', 'attachments')->download('file.txt');

// Get file content
$content = Vault::disk('local')->get('/tmp/file.txt');

// Delete file
Vault::disk('local')->delete('/tmp/file.txt');

// S3 usage
Vault::disk('s3', 'uploads')->add('/tmp/file.txt');
Vault::disk('s3')->delete('/tmp/file.txt');
```

## 15. Build, Test, Debug

- Install deps: `composer install`.
- Run tests: `composer test` (see `phpunit.xml`; test roots: `src/Tests/Unit`, `src/Tests/Feature`).
- Static analysis: `composer analyze` (phpstan).
- For local HTTP handling, include `vendor/autoload.php`, ensure `new Proto\Base()` is constructed (e.g., via `Api\ApiRouter`), and set `env('router')->basePath` in `common/Config/.env` if you need an API prefix.

## 16. Integration Points

- External deps in `composer.json`: AWS SDK, Twilio, PHPMailer, Web Push, JWT, OpenAI. Higher-level wrappers live under `src/Dispatch/*`, `src/Integrations/*`.
- Database access is abstracted in `src/Database/*` and `src/Storage/*` with query builders and policies.

## 17. Batch Enrichment Pattern

Use this pattern when a controller needs to append user-specific flags (e.g., `isFavorited`, `isBookmarked`) or computed properties sourced from related tables, where:

- Not all queries need the extra data (so eager joins in `joins()` are wasteful)
- A per-row lookup inside `all()` would create N+1 queries

The solution is always **3 queries total** regardless of result size: one main query + one batch `IN` query per related table, merged in PHP.

### enrichRow() Auto-Delegates to enrichRows()

By default, `enrichRow()` delegates to `enrichRows()`, so you only need to implement the batch version. The single-item `get()` path automatically reuses the same logic:

```php
// Only implement enrichRows() — enrichRow() delegates automatically
protected function enrichRows(array &$rows, Request $request): void
{
    $userId = session()->user->id;

    $this->batchMapExists(
        $rows, Bookmark::class,
        'itemId', 'bookmarked',
        [['userId', $userId], ['itemType', 'forum_post']]
    );
}
```

Override `enrichRow()` individually only if the single-item path genuinely needs different logic.

### BatchEnrichmentTrait

**Location**: `Proto\Controllers\Traits\BatchEnrichmentTrait`

Use this trait in controllers that need to batch-fetch related data in `enrichRows()`. It provides two declarative helpers that eliminate manual map-building:

```php
use Proto\Controllers\Traits\BatchEnrichmentTrait;

class ForumPostController extends ResourceController
{
    use BatchEnrichmentTrait;

    protected function enrichRows(array &$rows, Request $request): void
    {
        $userId = session()->user->id;

        // Map topic names: topicId → topicName
        $this->batchMapField(
            $rows, ForumTopic::class,
            'id', 'name', 'topicName', '',
            [], 'topicId'
        );

        // Map user votes: postId → voteType
        $this->batchMapField(
            $rows, ForumPostVote::class,
            'postId', 'voteType', 'userVote', null,
            [['userId', $userId]]
        );

        // Boolean: user bookmarked?
        $this->batchMapExists(
            $rows, Bookmark::class,
            'itemId', 'bookmarked',
            [['userId', $userId], ['itemType', 'forum_post']]
        );
    }
}
```

**Available methods**:
- `batchMapField($rows, $modelClass, $foreignKey, $valueField, $targetField, $default, $extraFilter, $sourceKey)` — batch-fetch a value from related records and map onto rows
- `batchMapExists($rows, $modelClass, $foreignKey, $targetField, $extraFilter, $sourceKey)` — batch-check existence and set a boolean flag

Both methods set defaults first (safe for unauthenticated users), then do a single `IN` query per call.

### Static Batch Fetch Methods on Related Models

For cases where you need more control, add a static method to each related model that accepts an array of parent IDs and returns the matching related IDs in one query:

```php
// In UserFavoriteVehicle model
public static function getIdsForUser(int $userId, array $vehicleIds): array
{
    if (empty($vehicleIds))
    {
        return [];
    }

    $results = static::fetchWhere([
        ['userId', $userId],
        ['vehicleId', 'IN', $vehicleIds]
    ]);
    return array_column($results, 'vehicleId');
}

// In Bookmark model
public static function getIdsForUser(int $userId, string $itemType, array $itemIds): array
{
    if (empty($itemIds))
    {
        return [];
    }

    $results = static::fetchWhere([
        ['userId', $userId],
        ['itemType', $itemType],
        ['itemId', 'IN', $itemIds]
    ]);
    return array_column($results, 'itemId');
}
```

### Enrichment Method on the Primary Model

Add a static `enrichWithUserData` method that sets default values first (covering the unauthenticated case), then overwrites with real values from the batch lookups:

```php
// In Vehicle model
use Modules\User\Models\UserFavoriteVehicle;
use Modules\Content\Models\Bookmark;

public static function enrichWithUserData(array $rows, ?int $userId): void
{
    foreach ($rows as $row)
    {
        $row->isFavorited = false;
        $row->isBookmarked = false;
    }

    if (!$userId || empty($rows))
    {
        return;
    }

    $ids = array_map(fn($row) => (int)$row->id, $rows);
    $favIds = array_flip(UserFavoriteVehicle::getIdsForUser($userId, $ids));
    $bookmarkIds = array_flip(Bookmark::getIdsForUser($userId, 'vehicle', $ids));

    foreach ($rows as $row)
    {
        $row->isFavorited = isset($favIds[$row->id]);
        $row->isBookmarked = isset($bookmarkIds[$row->id]);
    }
}
```

### Controller Override

Override `get()` and `all()` to call the enrichment after delegating to the parent:

```php
public function get(Request $request): object
{
    $result = parent::get($request);
    $row = $result->row?->getData();
    if (!$row)
    {
        return $result;
    }

    Vehicle::enrichWithUserData([$row], session()->user->id);
    $result->row = $row;
    return $result;
}

public function all(Request $request): object
{
    $result = parent::all($request);
    $rows = array_map(fn($item) => $item->getData(), $result->rows ?? []);
    if (empty($rows))
    {
        return $result;
    }

    Vehicle::enrichWithUserData($rows, session()->user->id);
    $result->rows = $rows;
    return $result;
}
```

**Result**: Always 3 queries — 1 main + 1 per related table — whether the list contains 1 item or 10,000.

### CRITICAL Rules
- **NEVER** call related model lookups per-row inside an `all()` loop — this is N+1 and will not scale
- **ALWAYS** batch-fetch related data with `IN` clause queries and merge in PHP
- Use `array_flip()` + `isset()` for O(1) set-membership checks instead of `in_array()`
- Set all flags to their default values first so the unauthenticated (null user) path requires no extra branching
- Prefer `BatchEnrichmentTrait` helpers (`batchMapField`/`batchMapExists`) over manual map-building
- For complex enrichment with custom static methods, the enrichment method belongs on the **model**, not in the controller — controllers only call it
- Only implement `enrichRows()` — `enrichRow()` auto-delegates by default

## 18. Anti-Patterns (What NOT to Do)

| ❌ WRONG | ✅ CORRECT |
|---------|-----------|
| `User::delete(1)` | `User::remove(1)` or `$user->delete()` |
| `new UserStorage()` in Controller | `User::fetchWhere([...])` |
| `$table->foreignKey('user_id')` | `$table->foreign('user_id')` |
| `function test() {` | `function test()\n{` |
| `$user = User::create($data);` | `$user = new User(); $user->add();` |
| `->where('a')->where('b')` | `->where('a', 'b')` |
| `->fetchOne()` | `->first()` |
| `$this->request` in `addItem()` | Use `modifyAddItem($data, $request)` hook |
| Override `addItem()` for route params | Use `$routeParams` property or `modifyAddItem()` |
| `modifyAddItem` just for route param | Use `protected array $routeParams = ['forumId' => true];` |
| `modifyFilter` just for query params | Use `protected array $filterParams = ['topicId' => 'int'];` |
| Manual user fields on add response | Use `protected array $enrichUserFields = [...]` |
| Duplicate `enrichRow`/`enrichRows` logic | Only implement `enrichRows()` — `enrichRow()` auto-delegates |
| Manual file loop for batch uploads | Use `$this->handleMediaUpload(...)` |
| Custom MIME fallback methods | Built into `UploadFile::getMimeType()` automatically |
| `protected function modifyAddItem()` | `protected function modifyAddItem()` (typo) |
| `\Modules\User\Models\User::get()` | `use Modules\User\Models\User; User::get()` |
| `$request->route('id')` | `$request->getInt('id')` or `$request->input('id')` |
| `if (!$userId) return error()` in controller | Remove check - policy handles auth |
| `$userId = session()->user->id ?? null;` | `$userId = session()->user->id;` after policy |
| `throw new \Exception()` in controller | `$this->setError()` or `$this->error()` |
| `$m = new Model(); $m->x = 1; $m->add();` | `$m = new Model((object)['x' => 1]); $m->add();` |
| `$_FILES['upload']` in controller | `$request->file('upload')` |
| `new UploadFile($_FILES['upload'])` | `$request->file('upload')` or `$request->validateFile('upload', [...])` |
| Per-row related lookups in `all()` loop | Use `enrichWithUserData()` with batch `IN` queries |
| Manual `$placeholders = implode(...)` for IN | `['field', 'IN', $array]` shorthand |
| `$this->service = new XService()` in constructor | `protected ?string $serviceClass = XService::class;` |
| Manual audit fields before service call | Auto-injected — service receives data with audit fields |

## 19. Configuration & Gotchas

- `common/Config/.env` is JSON (not dotenv). It must at least define `domain` (production/development) and optional `modules`/`services` arrays.
- In `dev` env, `ControllerHelper` skips policy/caching proxies for easier development. Production enables them.
- `Router::resource()` automatically adds `/:id?` to your URI; your controller can read `id` via `$req->params()->id` or `$req->getInt('id')`.

Questions or gaps: confirm how your app autoloads `Modules\*` namespaces (Composer PSR-4 in the host app) and how you want policies/caching configured by default in non-dev.