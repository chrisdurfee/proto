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

## Naming Conventions & Namespace Structure

- **Class Names:** Use **PascalCase** and they should be singular.
- **Methods & Variables:** Use **camelCase**.
- **Folder Names:** Use lowercase with hyphens to join words.
- **File Names:** Use **PascalCase**.
- **Namespaces:** Should reflect the folder structure to support autoloading.

## Configuration

Before you begin, configure your application settings in the **Common/Config** `.env` file. All settings should be registered as JSON.

The `Proto\Config` class loads these settings during bootstrap. It is a singleton; access configurations with:

```php
use Proto\Config;

// Retrieve configuration values
$config = Config::getInstance();
$baseUrl = $config->get('baseUrl');

// Or via the helper function:
$connections = env('connections');

   ```

## Screenshots

![Generator Page](https://raw.githubusercontent.com/chrisdurfee/proto/refs/heads/main/public/images/product/generator-page.png)
![Generator Modal](https://raw.githubusercontent.com/chrisdurfee/proto/refs/heads/main/public/images/product/generator-modal.png)
![Migration Page](https://raw.githubusercontent.com/chrisdurfee/proto/refs/heads/main/public/images/product/migration-page.png)
![Error Page](https://raw.githubusercontent.com/chrisdurfee/proto/refs/heads/main/public/images/product/error-page.png)
![Error Modal](https://raw.githubusercontent.com/chrisdurfee/proto/refs/heads/main/public/images/product/error-modal.png)
![Documentation Page](https://raw.githubusercontent.com/chrisdurfee/proto/refs/heads/main/public/images/product/documentation-page.png)

## Contributing

We welcome contributions from the community. If you would like to contribute to Proto, please follow these guidelines:

1. **Fork the Repository**: Fork the Proto repository on GitHub.
2. **Create a Branch**: Create a new branch for your feature or bugfix.
3. **Submit a Pull Request**: Submit a pull request with a detailed description of your changes.

## License

Proto is open-source software licensed under the [MIT license](LICENSE).

## Contact

For any questions or inquiries, please contact us at [support@protoframework.com](mailto:support@protoframework.com).
