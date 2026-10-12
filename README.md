# Proto Framework

## Introduction to Proto

Proto is an open-source modular monolith framework for building scalable server applications quickly and securely. It's secure, performant, and designed for developer productivity. It requires **PHP 8.4 or newer**. AI coding agents should read [.github/copilot-instructions.md](.github/copilot-instructions.md), the canonical guide to building with Proto.

## Overview

Distributed systems are great, except when they are not. Building large, team-based systems that scale presents many challenges—testing, conflicts, build times, response times, developer environments, etc. Proto was created to allow scalable server applications to be built rapidly and securely. Its modular design enables teams to develop specific features without many of the common pitfalls of distributed systems. Proto auto-bootstraps and loads modules on demand, and configuration is managed in the **Common/Config** `.env` file.

## Framework Features

Proto includes a comprehensive set of features for creating complex applications, including:

- **Modules system** to encapsulate features
- **API Systems** with REST Router
- **Validation** with sanitization
- **Server-Sent Events (SSE)**
- **Websockets & Sockets**
- **HTTP Resources**
- **Security Gates and Policies**
- **Error Tracking and Handling**
- **Authentication** using roles, permissions, and organizations
- **Controllers**
- **Caching (Redis)**
- **Configs** with .env JSON support
- **Models** with complex relationships (eager and lazy)
- **Collections** for data manipulation
- **Storage Layers** to abstract data storage from data sources
- **Session Management** (file, database, and Redis support)
- **Services & Service Providers**
- **Events, Event Loops** with async support
- **Jobs** with event queues (database, Kafka support)
- **Design Patterns**
- **HTML Templates** using components
- **Email Rendering** with Templates
- **Dispatching Email, SMS, and Web Push**
- **Resource Generators** for quick code scaffolding
- **Database Abstractions**
- **Database Adapters** with MySQLi support
- **Query Builders**
- **Database Migrations** with seeding support
- **Seeding** for testing
- **Factories** for generating test data
- **Testing** with PHPUnit, data faking, and robust utilities
- **Automations** to create routine tasks
- **File Storage** (local, AWS S3 support)
- **Integrations** to third-party services (REST, JWT, Oauth2 support)
- **Utilities** for dates, strings, files, encryption, and more

## File Structure

A typical Proto application is structured as follows:

- **common/**
	The root for your application code and shared components between modules.
- **modules/**
	Contains self-contained modules for each major domain or feature.
- **public/**
	Front-end assets and public resources.
- **vendor/protoframework/proto/**
	The core framework. This folder is accessible but should not be modified.

## Bootstrapping

Proto auto bootstraps when interfacing with an API, Controller, Model, Storage, or Routine. Simply include `/vendor/autoload.php` and call the namespaced classes you need.

```php
<?php declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

Proto\Api\ApiRouter::initialize();
```

There is no need for extensive manual setup; Proto handles loading, event registration, and other behind-the-scenes tasks automatically.

## Core Concepts

The core of Proto is its modular monolith design. Instead of a distributed system with many separate services, Proto encourages building a single application composed of isolated modules. This approach simplifies development, testing, and deployment while maintaining clear boundaries between features.

The common code resides in the `common/` directory, which contains shared components and utilities used across modules.

### Modules & Gateways

Each feature or domain is encapsulated in its own module under the `modules/` folder. Modules are self-contained but can communicate with other registered modules.

#### Modules

Each module contains its own APIs, controllers, models, and gateways. Modules help isolate features and enable independent testing and deployment.

The structure of a module:

```
modules/
	User/
		Api/ (endpoints)
		Controllers/ (request handlers)
		Models/ (data models)
		Gateway/ (public interface)
		UserModule.php (main module class to activate module)
```

An example Module class:

```php
<?php declare(strict_types=1);
namespace Modules\User;

use Proto\Module\Module;
use Modules\User\Auth\Gates\RoleGate;

/**
 * UserModule
 *
 * This module handles user-related functionality.
 *
 * @package Modules\User
 */
class UserModule extends Module
{
	/**
	 * This will activate the module.
	 *
	 * @return void
	 */
	public function activate(): void
	{
		$this->setConfigs();
		$this->setAuthGates();
	}

	/**
	 * Set the .env configs for the module
	 * to the global configs registry.
	 *
	 * @return void
	 */
	private function setConfigs(): void
	{
		setEnv('settingName', 'value');

		// also read from the global configs
		// $domain = env('domain');
	}

	/**
	 * This will set the authentication gates.
	 *
	 * @return void
	 */
	private function setAuthGates(): void
	{
		$auth = auth();

		/**
		 * This will register the role gate globally to be
		 * consumed by all modules.
		 */
		$auth->role = new RoleGate();
	}

	/**
	 * Add module events to the global events pub sub.
	 *
	 * @return void
	 */
	protected function addEvents(): void
	{
		/**
		 * Add an event for when a ticket is added.
		 */
		$this->event('Ticket:add', function(object $ticket): void
		{
			var_dump($ticket);
		});
	}
}
```

#### Gateways

Gateways provide a public interface for module methods. They allow other modules to call functionality without exposing the internal workings of the module. Gateways can support versioning for backward compatibility.

Example gateway implementation:

```php
<?php declare(strict_types=1);
namespace Modules\Example\Gateway;

/**
 * Gateway
 *
 * Gateways provide a public interface for module methods. They allow other modules to call functionality without exposing the internal workings of the module. Gateways can support
 * versioning for backward compatibility.
 *
 * @package Modules\Example\Gateway
 */
class Gateway
{
	/**
	 * Add a new example.
	 *
	 * @return void
	 */
	public function add(): void
	{
		// Implementation for adding an example.
	}

	/**
	 * Get the v1 gateway.
	 *
	 * @return V1\Gateway
	 */
	public function v1(): V1\Gateway
	{
		return new V1\Gateway();
	}

	/**
	 * Get the v2 gateway.
	 *
	 * @return V2\Gateway
	 */
	public function v2(): V2\Gateway
	{
		return new V2\Gateway();
	}
}
```

#### Accessing a Module Gateway

To access a module's gateway, use the global `modules()` function followed by the module name and version:

```php
modules()->user()->add($data);


// In another module anywhere. Usually in a controller
modules()->example()->add();

// To use versioned methods:
modules()->example()->v1()->add();
modules()->example()->v2()->add();

```

This is an example controller for the Auth module that calls the User module's gateway.

```php
<?php declare(strict_types=1);
namespace Modules\Auth\Controllers;

use Proto\Controllers\Controller;
use Proto\Http\Router\Request;

/**
 * AuthController
 *
 * This controller handles authentication-related actions.
 *
 * @package Modules\Auth\Controllers
 */
class AuthController extends Controller
{
	/**
	 * Register a new user.
	 *
	 * @return object
	 */
    public function register(): object
    {
		// Call the user module to add a new user.
        $result = modules()->user()->add();
		// do more things
    }
}
```

#### Module Registration

For a module to be valid and loaded, it must be registered in your configuration file (e.g. in the common .env file) under the "modules" key. For example:

```json
{
	"modules": [
		"Example\\ExampleModule",
		"Product\\ProductModule",
		"User\\UserModule"
	]
}
```

### API Routing

API routes for a module are defined in an `api.php` file within the module's API folder. Nested folders allow for deep API paths.

Basic API route example:

```php
<?php declare(strict_types=1);
namespace Modules\User\Api;

use Modules\User\Controllers\UserController;
use Proto\Http\Middleware\CrossSiteProtectionMiddleware;

/**
 * User API Routes
 *
 * This file contains the API routes for the User module.
 */
router()
	->middleware([
		CrossSiteProtectionMiddleware::class
	])
	->resource('user', UserController::class);
```

api.php files can be nested in subfolders for better organization. Here's a nested API route example:

```
// the structure of the api folder
User/
  Api/
    Account/
      api.php (nested account file)
	api.php (root api file)
```

The file `User/Api/Account/api.php` contains:

```php
<?php declare(strict_types=1);
namespace Modules\User\Api\Account;

use Modules\User\Controllers\UserController;

/**
 * User API Routes for Accounts
 *
 * This file handles API routes for user accounts.
 */
router()
	->resource('user/:userId/account', UserController::class);
```

This helps organize and manage API routes more effectively.

#### Complex API Route Example
Here is a more complex complex example of an api file with multiple routes and middleware applied:
```php
<?php declare(strict_types=1);
namespace Modules\Auth\Api;

use Modules\Auth\Controllers\AuthController;
use Modules\Auth\Controllers\PasswordController;
use Proto\Http\Middleware\CrossSiteProtectionMiddleware;
use Proto\Http\Middleware\DomainMiddleware;
use Proto\Http\Middleware\ThrottleMiddleware;
use Proto\Http\Router\Router;

/**
 * Auth API Routes
 *
 * This file defines the API routes for user authentication, including
 * login, logout, registration, MFA, and CSRF token retrieval.
 */
router()
	->middleware(([ // add CSRF protection to all auth routes
		CrossSiteProtectionMiddleware::class
	]))

	// this will individually add routes by method
	->post('auth/pulse', [AuthController::class, 'pulse'])
	->post('auth/register', [AuthController::class, 'register'])

	// this will add a route and add a middleware to the route
	->get('auth/csrf-token', [AuthController::class, 'getToken'], [
		DomainMiddleware::class
	]);

/**
 * A group of routes that require throttling to prevent abuse.
 */
router()
	->middleware(([ // add throttling to auth routes below
		ThrottleMiddleware::class
	]))

	// this will add a group of routes with a common prefix
	->group('auth', function(Router $router)
	{
		$controller = new AuthController();
		// standard login / logout / register
		$router->post('login', [$controller, 'login']);
		$router->post('logout', [$controller, 'logout']);
		$router->post('resume', [$controller, 'resume']);

		// MFA: send & verify one‑time codes
		$router->post('mfa/code', [$controller, 'getAuthCode']);
		$router->post('mfa/verify', [$controller, 'verifyAuthCode']);

		// Password reset: request & verify reset codes
		$controller = new PasswordController();
		$router->post('password/request', [$controller, 'requestPasswordReset']);
		$router->post('password/verify', [$controller, 'validatePasswordRequest']);
		$router->post('password/reset', [$controller, 'resetPassword']);
	});

```

The API router only loads the `api.php` files within each module's API directory or subdirectory if it matches the router's path. This makes it efficient, only registering routes that would trigger without loading all routes.

### Controllers

Controllers are classes used to manage data, handle HTTP requests, validate input, and return standardized responses. They can access models, integrations, or other controllers, and can dispatch email, text, and web push notifications. Proto provides parent controller classes with built-in CRUD methods so child controllers don't need to reimplement common functionality.

#### Naming Convention

Controller names should always be singular and followed by "Controller":

```php
<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Modules\User\Models\User;
use Proto\Controllers\ModelController;

class UserController extends ModelController
{
}
```

#### Controller Types

Proto provides several controller base classes:

- **Controller**: Basic controller for general use
- **ApiController**: For handling HTTP requests with built-in request helpers and validation
- **ResourceController**: Provides full RESTful CRUD functionality for a model
- **ModelController**: For controllers that primarily interact with a single model

#### Resource Controllers

Resource controllers are used with the router's `resource()` method to automatically handle RESTful operations. They provide the following default methods:

- `all(Request $request)`: GET all items
- `get(Request $request)`: GET a single item by ID
- `add(Request $request)`: POST to create a new item
- `setup(Request $request)`: PUT add or update
- `update(Request $request)`: PATCH to update an item
- `delete(Request $request)`: DELETE an item

Example resource controller:

```php
<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Modules\User\Models\User;
use Proto\Controllers\ResourceController;
use Proto\Http\Router\Request;

class UserController extends ResourceController
{
	public function __construct(
		protected ?string $model = User::class
	)
	{
		parent::__construct();
	}

	/**
	 * Override the add method to include custom validation.
	 *
	 * @param Request $request
	 * @return object
	 */
	public function add(Request $request): object
	{
		$data = $this->getRequestItem($request);
		if (empty($data) || empty($data->username))
		{
			return $this->error('No item provided.');
		}

		$isTaken = User::isUsernameTaken($data->username ?? '');
		if ($isTaken)
		{
			return $this->error('Username is already taken.');
		}

		return $this->addItem($data);
	}
}
```

#### API Controllers

API controllers handle custom HTTP endpoints that don't fit the standard CRUD pattern. They extend `ApiController` and receive the `Request` object:

```php
<?php declare(strict_types=1);
namespace Modules\User\Controllers;

use Proto\Controllers\ApiController;
use Proto\Http\Router\Request;

class SummaryController extends ApiController
{
	/**
	 * Get user summary data.
	 *
	 * @param Request $request
	 * @return object
	 */
	public function getSummary(Request $request): object
	{
		$userId = $request->params()->userId ?? null;
		if (!$userId)
		{
			return $this->error('User ID is required.');
		}

		// Fetch summary data
		$summary = $this->getUserStats($userId);

		return $this->success($summary);
	}

	private function getUserStats(int $userId): array
	{
		// Implementation here
		return [];
	}
}
```

#### Controller Responses

Controllers return standardized response objects that encapsulate data, success status, and error messages. This standardization is used by the API system.

Available response methods:

```php
// Success response with data
return $this->success(['items' => $data], 200);

// Error response with message
return $this->error('No user was found', 404);

// Generic response wrapper (handles null checks)
return $this->response($result, 'Custom message');
```

Response examples:

```php
// Single row
protected function getByName(string $name): object
{
	$row = $this->model::getBy(['name' => $name]);
	if ($row === null)
	{
		return $this->error('No user was found');
	}

	return $this->response($row);
}

// Multiple rows
protected function getAllActive(): object
{
	$rows = $this->model::fetchWhere(['status' => 'active']);
	if (empty($rows))
	{
		return $this->error('No users were found');
	}

	return $this->success(['items' => $rows]);
}

// Custom query
protected function getRecentByName(string $name): object
{
	$rows = $this->model::where(['name' => $name])
		->orderBy('id DESC')
		->groupBy('id')
		->limit(10)
		->fetch();

	if (empty($rows))
	{
		return $this->error('No users were found');
	}

	return $this->response($rows);
}
```

#### Custom Methods

Controllers can have custom methods to extend functionality:

```php
public function resetPassword(Request $request): object
{
	$data = $this->getRequestItem($request);

	// Validate required fields
	if (empty($data->token) || empty($data->password))
	{
		return $this->error('Token and password are required.');
	}

	// Create a model instance with the provided data
	$model = $this->model($data);

	// Process the password reset action via the model
	$result = $model->resetPassword();
	return (!$result) ? $this->error('Password reset failed.') : $this->response($result);
}
```

#### Request Data Handling

Controllers provide methods for accessing request data:

```php
public function add(Request $request): object
{
	// Get the request item (by default looks for 'item' key)
	$user = $this->getRequestItem($request);

	// Or access request data directly
	$username = $request->input('username');
	$email = $request->input('email');
	$status = $request->getInt('status');
	$save = $request->getBool('save');
	$user = $request->json('user');

	// Params
	$userId = $request->params()->userId;

	// Do something with the data
}
```

You can customize the request item key:

```php
class UserController extends ResourceController
{
	// Override default 'item' key
	protected string $item = 'user';

	public function add(Request $request): object
	{
		// Now looks for 'user' key in request
		$user = $this->getRequestItem($request);
	}
}
```

#### Validation & Sanitization

Proto includes a powerful validator that sanitizes and validates data by type. The `validate()` method defines validation rules:

```php
/**
 * Validation rules for this controller.
 */
protected function validate(): array
{
	return [
		'id' => 'int|required',
		'name' => 'string:255|required',
		'email' => 'email|required',
		'phone' => 'phone',
		'status' => 'int',
		'website' => 'url',
		'age' => 'int'
	];
}
```

Supported validation types:

- `int`: Integer
- `float`: Floating point number
- `string`: String (with optional length limit using `:number`)
- `email`: Email address
- `ip`: IP address
- `phone`: Phone number
- `mac`: MAC address
- `bool`: Boolean
- `url`: URL
- `domain`: Domain name
- `image` / `file`: Uploaded file (max size in KB, e.g. `image:2048`), with an optional `mimes:jpeg,png` sub-rule

Validation modifiers:

- `required`: Field is required
- `:number`: Set max length (e.g., `string:255`)

Custom validation in methods. `validateRules()` validates `$data` (an object is sanitized in place) and returns `true`; on failure it stops the request with a `422` error response that includes per-field errors, so the code after it only runs for valid data:

```php
public function addUser(Request $request): object
{
	$data = $this->getRequestItem($request);
	$this->validateRules($data, [
		'username' => 'string:50|required',
		'email' => 'email|required',
		'age' => 'int'
	]);

	return $this->addItem($data);
}
```

`$request->validate($rules)` is a shorthand that returns the validated item object (or `null` when no item was sent). On failure it sends a `400` response itself and exits, without per-field errors. Prefer `validateRules()` in controllers.

#### Getting Resource ID

Resource controllers provide a helper to get the resource ID from request parameters:

```php
public function updateStatus(Request $request): object
{
	$id = $this->getResourceId($request);
	$status = $request->input('status');

	if ($id === null || $status === null)
	{
		return $this->error('ID and status are required.');
	}

	return $this->response(
		$this->model((object) [
			'id' => $id,
			'status' => $status
		])->updateStatus()
	);
}
```

#### Accessing Models and Storage

Controllers can instantiate their associated model and access storage:

```php
// Create a model instance with data
$model = $this->model($data);
$result = $model->update();

// Access model storage directly for complex queries.
// Bind values with [column, value] tuples (or $sql->addParam()): the
// callback receives $params by value, so pushing onto it has no effect.
$users = $this->storage()->findAll(function($sql) {
	$sql->where(['status', 'active'])
		->orderBy('created_at DESC')
		->limit(50);
});

// Single row with custom query
$user = $this->storage()->find(function($sql) {
	$sql->where(['email', 'john@example.com']);
});
```

#### Pass-Through Model Methods

`ModelController` and `ResourceController` forward calls to methods the controller does not declare to the model, but only for methods listed in `$allowedModelMethods`. The default list is `get`, `getBy`, `fetchWhere`, `all`, `search`, and `count`. Any other undeclared method returns an error response (`The method is not callable.`); it is never forwarded.

```php
class UserController extends ResourceController
{
	// Add model methods that may be called through the controller.
	protected array $allowedModelMethods = ['get', 'getBy', 'fetchWhere', 'all', 'search', 'count', 'getUsersByRole'];
}

// Instance call: the model result is wrapped as ['rows' => ...] (array) or ['row' => ...] and returned as a response
$result = $controller->getUsersByRole('admin');

// Static call: returns the raw model result, still limited to the allow-list
$rows = UserController::getUsersByRole('admin');
```

Methods the controller declares itself (for example `ResourceController::get()` or `all()`) are always called directly. Prefer a declared controller method over widening the allow-list when the endpoint needs validation or authorization logic.

#### Resource Controller Conventions

`ResourceController` can own filters, flags, lookups, and lifecycle without overriding `all()` / `get()` / `add()`.

**Qualify filters.** Unqualified keys that match the model `$fields` are prefixed with the model alias so joins do not make `status` ambiguous. Default is on; set `$qualifyFilters = false` to opt out.

```php
protected bool $qualifyFilters = true;
```

If a controller's own filter service reads **named, unprefixed** properties off the raw client filter (e.g. `$clientFilter->isPinned`) before building its own SQL, `$qualifyFilters` auto-prefixing runs on the wrong shape. Either keep `$qualifyFilters = false` and qualify only the final built fragments via `Filter::aliased()`, or read from `rawRequestFilter()` (below) instead of the auto-qualified filter.

**Declarative enrichments.** Declare batch flags, copied fields, and counts. They run on `get()` and `all()` before `enrichRows()`. Use `'include' => 'name'` to gate an enrichment behind `?include=`.

```php
protected array $currentUserFlags = [
	'liked' => ['model' => PostLike::class, 'foreignKey' => 'postId']
];

protected array $enrichments = [
	'commentCount' => [
		'type' => 'count',
		'model' => Comment::class,
		'foreignKey' => 'postId',
		'include' => 'stats'
	]
];
```

**Allowlisted includes.** `?include=author,stats` is ignored unless the name is in `$allowedIncludes`. Put always-on extras in `$defaultIncludes`. Models may implement `includeJoins($builder, array $includes)` for optional joins; keep identity/privacy joins in `joins()`.

```php
protected array $allowedIncludes = ['author', 'stats'];
protected array $defaultIncludes = [];
```

**List scopes.** Model `$scopes` run on every `Model::all()` / `getRows()` / `fetchWhere()`, and ResourceController also applies them (plus controller `$scopes` and `Policy::scope()`) on `all()` and `get()`. Direct `Model::get($id)` does not apply scopes, so internal writes can still load a row. `VisibleScope` is owner OR (`privacy=public` AND `status=published`) — it is a thin preset of `Proto\Models\Scopes\OwnershipVisibilityScope`, which takes an arbitrary list of AND'd `[column, operator, value]` visibility conditions (e.g. `status='active' AND archivedAt IS NULL`) for controllers whose visibility rule isn't the public/published shape.

**Lookups and PATCH.** `$lookupKeys` accepts uuid/slug after numeric `id`. `rulesForPartialUpdate()` strips `required` for omitted PATCH fields.

```php
protected array $lookupKeys = ['id', 'guid', 'slug'];
```

**Lifecycle.** Override `afterAdd` / `afterUpdate` / `afterDelete`. Successful mutations also emit `{model}.created|updated|deleted` via `events()->emit()`.

**Shared cache.** ModelPolicy namespaces keys by user or session. `$cacheSharedPayload = true` shares **lists only** (`all()` keys include `applyListScopes()`); `get()` stays user/session scoped. Viewer flags are stripped before a shared list is stored and re-applied after a hit. Declare every viewer-specific field in `$currentUserFlags` or `$enrichments` type `flag`.

**Routing.** Use `router()->resourceStrict('article', ArticleController::class)` instead of `resource()` when the same prefix has item ids and literal children. `ResourceHelper` defers activation in `api.php` and prefers static segments, so `article/featured` wins over `article/:id` in either order. Use `router()->includeApi(__DIR__ . '/Featured/api.php')` to compose sibling api files. Outside `api.php`, call `deferActivation()` / `flushDeferred()` or register the literal child first.

**Services.** `Proto\Services\Service` provides `success()`, `failure()`, `restrictFields()`, and `generateUuid()`. Set `$serviceClass` on the controller instead of constructing a service in `__construct()`. Gateways memoize children with `$this->gateway(ChildGateway::class)`. Classes that can't extend `Proto\Services\Service` (e.g. a hand-copied base with its own `success()` that would collide) can `use Proto\Services\Traits\ServiceResultFactory;` for `ok()` / `fail()` helpers that build the same `ServiceResult` without a base-class change.

**Filters.** Request `filter` JSON cannot carry raw SQL fragments. Columns are allowlisted to the model's filterable fields ( `$fields` minus secrets, or `$filterableFields`). Request `IN` / `NOT IN` lists are capped at 100. App-built parameterized entries (`[sql, [params]]`) still work when you append them after `getFilter()`. For sync windows use `Filter::since()` (bound params) or `Filter::sinceLiteral()` / `Filter::isSafeTimestamp()` when a query builder interpolates the clause. Keys in `protected array $passthroughFilterKeys = [];` bypass the model-fields allowlist (still parameterized, still IN-capped) for controllers that need a client filter key that isn't a real column. `rawRequestFilter()` returns the raw decoded client filter before allowlisting for controllers that need to read named properties off it directly.

```php
$since = Filter::sinceLiteral('a', (string)$updatedAfter, ['updatedAt', 'createdAt']);
if ($since !== null)
{
	$sql->orWhere($since);
}
```

**Search.** Model `$searchableFields` defaults to `[]` (off). Set `['*']` to infer from `$fields` minus blacklist, audit, and secret columns (`email`, `phone`, `token`, `apiKey`, and similar). An explicit list is used as-is.

**Sorting.** Client `orderBy`, `groupBy`, and `dates.field` are limited to `Model::sortableFields()`: by default `$fields` minus blacklisted, secret, and PII columns (`id` and audit timestamps stay sortable). Columns outside the list are silently dropped. Set `protected static ?array $sortableFields = [...]` on the model to use an explicit list.

**Errors.** Uncaught exceptions in a route return JSON `{"success": false, "message": "..."}` with status 500, or 503 with `Retry-After` when the database is unreachable (`Proto\Database\DatabaseUnavailableException`). Exception details appear in the message only in dev.

**Other helpers.** `UserJoinFields` presets keep email/mobile off public user joins. `BatchMap` is the service-layer version of `BatchEnrichmentTrait`. `UnionFeed`, `PreferenceScorer`, and `SeenDemotion` merge and rank heterogeneous feeds in PHP. `Model::countGroupedBy()` issues `SELECT fk, COUNT(*) … GROUP BY fk`. Resource generation also scaffolds factory, seeder, and service files.

## Developer Tools

The framework package ships the building blocks that a developer dashboard uses. The dashboard UI itself is not part of this repository (an app may install one under `public/developer`, which is gitignored here).

- **Error tracking**: set `"errorTracking": true` in `common/Config/.env` to record errors in the `proto_error_log` table (`Proto\Error\Models\ErrorLog`).
- **Migrations**: `Proto\Database\Migrations\Guide` applies (`run()`) and reverts (`revert()`) migrations from the framework (`vendor/protoframework/proto/src/Migrations`), `common/Migrations`, and `modules/*/Migrations`.
- **Generators**: `Proto\Generators\Generator` scaffolds resources (`createResource()` writes the model, controller, API, and storage files, plus a factory, seeder, and service when they do not exist yet). It also creates migrations, tests, and tables.
- **Realtime server**: `vendor/bin/proto-realtime` starts the standalone SSE server (see [docs/REALTIME_SERVER.md](docs/REALTIME_SERVER.md)).

More guides are indexed in [docs/README.md](docs/README.md).

## Getting Started

### Installation

Proto requires PHP 8.4 or newer.

1. Add the package to your application with Composer:
```sh
composer require protoframework/proto
```

2. Create `common/Config/.env` (JSON, not dotenv) in your application root. The framework's [common/Config/.env-example](common/Config/.env-example) shows the expected keys (`env`, `domain`, `connections`, `modules`, `services`, `session`, `cache`, and so on).

3. Point your web server at a front controller that includes `vendor/autoload.php` and calls `Proto\Api\ApiRouter::initialize()` (see [Bootstrapping](#bootstrapping)).

## Contributing

Contributions are welcome! To contribute:

1. Fork the Repository on GitHub
2. Create a Branch for your feature or bug fix
3. Commit Your Changes using Conventional Commits (e.g. `fix(security): ...`)
4. Run `php vendor/bin/phpunit` and `composer analyze`
5. Submit a Pull Request with a detailed description of your changes

**Releases:** Packagist uses git tags for versioning; do not set a `"version"` field in `composer.json`.

See [CONTRIBUTING.md](CONTRIBUTING.md) for development setup, coding standards, and the changelog policy.

## License

Proto is open-source software licensed under the MIT License.

## Contact

For questions or support, please reach out via:

- GitHub Issues: [Proto Issues](https://github.com/chrisdurfee/proto/issues)
- Community Chat: (Coming soon)