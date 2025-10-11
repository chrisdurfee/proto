# Testing Database Setup

## Overview

The Proto framework uses a simplified, environment-based approach for database testing. Instead of manually creating separate test connections and complex overrides, the system leverages the existing environment configuration to automatically route database connections to the correct environment settings.

## How It Works

### 1. Environment-Based Connection Routing

The framework's `DatabaseManager` supports environment-specific connection settings:

```json
{
  "connections": {
    "default": {
      "dev": {
        "host": "127.0.0.1",
        "database": "proto",
        "username": "root",
        "password": "root",
        "port": 3306
      },
      "testing": {
        "host": "127.0.0.1",
        "database": "proto_test",
        "username": "root",
        "password": "root",
        "port": 3306
      },
      "prod": {
        "host": "127.0.0.1",
        "database": "proto_prod",
        "username": "root",
        "password": "root",
        "port": 3306
      }
    }
  }
}
```

### 2. Setting the Test Environment

In `Test::setupSystem()`, we simply set the environment to 'testing':

```php
protected function setupSystem(): void
{
    new Base();

    // This routes all 'default' connections to use 'testing' environment settings
    setEnv('env', 'testing');

    Error::disable();
}
```

### 3. Connection Caching for Transaction Isolation

The test setup enables connection caching to ensure all database operations share the same connection:

```php
protected function setupTestEnvironment(): void
{
    if (defined('BASE_PATH'))
    {
        // Enable caching so all connections return the same instance
        setEnv('dbCaching', true);

        // Begin transaction for test isolation
        if ($this->useTransactions)
        {
            $this->beginDatabaseTransaction();
        }
    }
}
```

### 4. Simplified Database Connection

The `DatabaseTestHelpers` trait creates test connections using the standard `Database::getConnection()` method:

```php
protected function createTestDatabase(): void
{
    // Get the default connection which automatically uses 'testing' env settings
    $db = Database::getConnection('default', true);

    if (!$db)
    {
        throw new \RuntimeException('Failed to create test database connection');
    }

    // Disable autocommit for transaction control
    $db->autoCommit(false);

    $this->testDatabase = $db;
}
```

## Benefits

### No Manual Connection Overrides
- No need to manually instantiate `Mysqli` with specific settings
- No need to cache connections with special connection names
- No need to override `TableStorage::setDefaultConnection()`

### Leverages Existing Infrastructure
- Uses the framework's built-in environment-based connection resolution
- Respects the same configuration patterns used in production
- Reduces code duplication and complexity

### Consistent Behavior
- All code (models, factories, seeders) automatically uses test database
- No special configuration needed in seeders or models
- Works seamlessly with the existing `Database` and `Config` classes

### Easy Configuration
- Simply set different database names per environment in `.env`
- Change `env` setting to switch between environments
- No code changes needed to switch databases

## Example Configuration

### Development & Testing Databases

```json
{
  "env": "dev",
  "connections": {
    "default": {
      "dev": {
        "database": "myapp_dev"
      },
      "testing": {
        "database": "myapp_test"
      },
      "prod": {
        "database": "myapp_prod"
      }
    }
  }
}
```

When tests run, they set `env` to `'testing'`, which automatically routes the `'default'` connection to use `myapp_test` database.

## Migration from Old Approach

### Before (Complex)
```php
// Manually create connection with specific settings
$settings = Config::getInstance()->getDBSettings('testing');
$settings->persistent = false;
$this->testDatabase = new Mysqli($settings, true);

// Cache with special connection name
ConnectionSettingsCache::set('testing', $this->testDatabase);

// Override storage default
TableStorage::setDefaultConnection('testing');
```

### After (Simple)
```php
// Set environment to testing (done once in Test::setupSystem)
setEnv('env', 'testing');

// Get connection normally - automatically uses testing settings
$db = Database::getConnection('default', true);
```

## Best Practices

1. **Use separate test databases**: Always use a different database for testing to avoid data conflicts
2. **Enable caching**: Set `dbCaching` to `true` in tests for transaction isolation
3. **Use transactions**: Let the framework automatically rollback changes after each test
4. **Standard connection names**: Use `'default'` for connections - let the environment handle routing

## Troubleshooting

### Tests Not Isolated
- Ensure `setEnv('dbCaching', true)` is set in `setupTestEnvironment()`
- Verify `useTransactions = true` in your test class

### Wrong Database Being Used
- Check that `setEnv('env', 'testing')` is called in `setupSystem()`
- Verify your `.env` file has `testing` settings under the `default` connection

### Connection Issues
- Ensure the test database exists and credentials are correct
- Check that the database user has proper permissions
