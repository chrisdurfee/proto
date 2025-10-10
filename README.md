# Proto Framework

## Introduction to Proto

Proto is an open-source modular monolith framework for building scalable server applications quickly and securely. It's secure, performant, and designed for developer productivity. It includes an AI document to help agents build with Proto.

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
- **Session Management** (database and file support)
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

// Bootstrap the application
require_once __DIR__ . '/vendor/autoload.php';

$data = (object)[];
// Example: Create a user via the User module gateway
modules()->user()->v1()->createUser($data);
```

There is no need for extensive manual setup; Proto handles loading, event registration, and other behind-the-scenes tasks automatically.

## Core Concepts

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

Nested API route example:

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

A complex example:
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

## Developer Tools

Proto includes a developer application located in `public/developer` that offers:
- Error tracking
- Migration management
- Generator system for scaffolding modules, gateways, APIs, controllers, and models

Use this tool to quickly scaffold new features or manage existing ones without needing a fully distributed microservices setup.

## Getting Started

### Installation

1. Install package using Composer:
```sh
cd proto
composer install protoframework/proto
```

or start your PHP server.

## Contributing

Contributions are welcome! To contribute:

1. Fork the Repository on GitHub
2. Create a Branch for your feature or bug fix
3. Commit Your Changes with clear, descriptive messages
4. Submit a Pull Request with a detailed description of your changes

Please follow our `CONTRIBUTING.md` for coding standards and guidelines.

## License

Proto is open-source software licensed under the MIT License.

## Contact

For questions or support, please reach out via:

- GitHub Issues: [Proto Issues](https://github.com/chrisdurfee/proto/issues)
- Community Chat: (Coming soon)