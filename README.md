# Proto Framework

## Introduction to Proto

Proto is an open-source modular monolith framework for building scalable server applications quickly and securely.

## Overview

Distributed systems are great, except when they are not. Building large, team-based systems that scale presents many challenges—testing, conflicts, build times, response times, developer environments, etc. Proto was created to allow scalable server applications to be built rapidly and securely. Its modular design enables teams to develop specific features without many of the common pitfalls of distributed systems. Proto auto-bootstraps and loads modules on demand, and configuration is managed in the **Common/Config** `.env` file.

## Framework Features

Proto includes a comprehensive set of features for creating complex applications, including:

- **Modules system** to encapsulate features
- **API Systems** (Both Resource and REST Routers)
- **Validation**
- **Server-Sent Events (SSE)**
- **Websockets & Sockets**
- **HTTP Resources**
- **Security Gates and Policies**
- **Authentication** using roles and permissions
- **Controllers**
- **Caching (Redis)**
- **Configs**
- **Models**
- **Storage Layers**
- **Session Management**
- **Services & Service Providers**
- **Jobs & Routines**
- **Design Patterns**
- **HTML Templates**
- **Email Rendering**
- **Dispatching Email, SMS, and Web Push**
- **Events**
- **Resource Generators**
- **Database Adapter**
- **Query Builders**
- **Migrations**
- **File Storage** (Local, S3)
- **Integrations**
- **Utilities**

## File Structure

A typical Proto application is structured as follows:

- **common/**
  The root for your application code and shared components between modules.
- **proto/**
  The core framework. This folder is accessible but should not be modified.
- **modules/**
  Contains self-contained modules for each major domain or feature.
- **public/**
  Front-end assets and public resources (including the developer app in `public/developer`).

## Bootstrapping

Proto auto bootstraps when interfacing with an API, Controller, Model, Storage, or Routine. Simply include `/vendor/autoload.php` and call the namespaced classes you need.

```php
<?php declare(strict_types=1);

// Bootstrap the application
require_once __DIR__ . '/vendor/autoload.php';

// Example: Create a user via the User module gateway
modules()->user()->v1()->createUser($data);
```

There is no need for extensive manual setup; Proto handles loading, event registration, and other behind-the-scenes tasks automatically.

## Core Concepts

### Modules & Gateways

Each feature or domain is encapsulated in its own module under the `modules/` folder. Modules are self-contained but can communicate with other registered modules.

#### Modules

Each module contains its own APIs, controllers, models, and gateways. Modules help isolate features and enable independent testing and deployment.

#### Gateways

Gateways provide a public interface for module methods. They allow other modules to call functionality without exposing the internal workings of the module. Gateways can support versioning for backward compatibility.

Example gateway implementation:

```php
<?php declare(strict_types=1);
namespace Modules\Example\Gateway;

class Gateway
{
    public function add(): void
    {
        // Implementation for adding an example.
    }

    public function v1(): V1\Gateway
    {
        return new V1\Gateway();
    }

    public function v2(): V2\Gateway
    {
        return new V2\Gateway();
    }
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