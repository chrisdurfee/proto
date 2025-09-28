# Copilot instructions for this repo

These notes teach AI agents how to be productive in this framework codebase quickly. Stick to the patterns and files referenced below.

## Big picture
- This is the Proto framework (modular monolith for PHP ≥ 8.1). Core lives under `src/` and is installed as a Composer package; apps using Proto keep app code in `modules/` and shared config in `common/Config/.env` (JSON).
- Bootstrapping happens via `Proto\Base` → `Proto\System` + `Config`, then `ModuleManager::activate(env('modules'))` and `ServiceManager::activate(env('services'))`.
- HTTP stack: `src/Http/Router/*` implements a lightweight router with middleware, request/response wrappers, and a resource pattern that maps HTTP verbs to controller methods.
- API front door: `src/Api/ApiRouter.php` sets up global helpers and routes. It dynamically includes `modules/<Module>/Api/**/api.php` via `ResourceHelper` for URLs like `/user/account/...`.

## Key conventions and helpers
- Global helpers (defined in `Config.php` and `Api/ApiRouter.php`):
  - `env($key)` returns config (from `common/Config/.env`). `envUrl()` gives the base URL. `ENV_URL` is a constant.
  - `router()` returns the singleton `Proto\Http\Router\Router` instance; use it to register routes.
  - `session()` gets the session; `modules()` accesses gateways registered by modules; `registerModule($key, $factory)` registers gateways.
- Router patterns (`src/Http/Router/Router.php`):
  - Route methods: `get|post|put|patch|delete|all($uri, $callback, ?$middleware)`.
  - Controller callbacks can be arrays like `[ControllerClass::class, 'method']`; they’re resolved via `ControllerHelper` (policies/caching may wrap controllers).
  - `resource($uri, $controller)` auto-maps `GET/POST/PUT/PATCH/DELETE` to controller methods `all|get|add|setup|update|delete` and appends `/:id?`.
  - Use `group('admin', fn($r)=>{ ... }, [Middleware::class])` to scope base paths and middleware.
- Controller patterns (`src/Controllers/*`):
  - Extend `Proto\Controllers\Controller` or `ApiController` (adds request helpers/validation). Set `protected ?string $policy = SomePolicy::class` to enable policy wrapping (bypassed in `env('env') === 'dev'`).
  - Return structured objects; use `$this->success($data, $code)`, `$this->error($message, $code)`, or `$this->response($result, $errorMessage)` to unify output. The router translates this into JSON with status code.
- Request/validation:
  - In controllers use `Proto\Http\Router\Request` for clean inputs: `$req->all()`, `$req->input('key')`, `$req->json('item')`, `$req->validate($rules, 'item')` (via `Proto\Api\Validator`).
- Middleware:
  - Any class with `handle(Request $req, callable $next)` can be registered. Built-ins include `ApiRateLimiterMiddleware`, `CrossSiteProtectionMiddleware`, `DomainMiddleware`, `RateLimiterMiddleware`.
- Resource resolution (`src/Api/ResourceHelper.php`):
  - URL `/user/account/details` maps to file `modules/User/Api/Account/Details/api.php` (PascalCase folders, numeric segments stripped, dots removed). In that file, call `router()->resource('user/account', Controller::class);` or add routes directly.

## Concrete examples
- Define a module API file `modules/User/Api/api.php`:
  - `router()->resource('user', Modules\User\Controllers\UserController::class);`
- Minimal controller signature (`Modules\User\Controllers\UserController`):
  - Methods: `all(Request $req)`, `get(Request $req)`, `add(Request $req)`, `setup(Request $req)`, `update(Request $req)`, `delete(Request $req)`.
  - Return `$this->success(['items' => []]);` or `$this->error('Not found', 404);`.
- Gateways (`src/Module/ModuleManager.php`, `src/Module/Modules.php`):
  - After activation, access gateways as `modules()->user()->v1()->createUser($data)` (your app defines these in each module’s `Gateway/Gateway.php`).

## Build, test, debug
- Install deps: `composer install`.
- Run tests: `composer test` (see `phpunit.xml`; test roots: `src/Tests/Unit`, `src/Tests/Feature`).
- Static analysis: `composer analyze` (phpstan).
- For local HTTP handling, include `vendor/autoload.php`, ensure `new Proto\Base()` is constructed (e.g., via `Api\ApiRouter`), and set `env('router')->basePath` in `common/Config/.env` if you need an API prefix.

## Integration points
- External deps in `composer.json`: AWS SDK, Twilio, PHPMailer, Web Push, JWT, OpenAI. Higher-level wrappers live under `src/Dispatch/*`, `src/Integrations/*`.
- Database access is abstracted in `src/Database/*` and `src/Storage/*` with query builders and policies.

## Gotchas
- `common/Config/.env` is JSON (not dotenv). It must at least define `domain` (production/development) and optional `modules`/`services` arrays.
- In `dev` env, `ControllerHelper` skips policy/caching proxies for easier development. Production enables them.
- `Router::resource()` automatically adds `/:id?` to your URI; your controller can read `id` via `$req->params()->id` or `$req->getInt('id')`.

Questions or gaps: confirm how your app autoloads `Modules\*` namespaces (Composer PSR-4 in the host app) and how you want policies/caching configured by default in non-dev.
