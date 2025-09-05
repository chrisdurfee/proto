# Proto Framework Seeder System

The Proto framework now includes a comprehensive database seeder system for populating databases with test data, initial data, or sample data.

## Overview

The seeder system provides:
- Abstract base seeder class with common database operations
- Seeder manager for organizing and running multiple seeders
- Built-in support for table operations (insert, truncate, isEmpty)
- Integration with the testing system for test data seeding

## Basic Usage

### Creating a Seeder

All seeder classes should extend the `Proto\Database\Seeders\Seeder` base class:

```php
<?php declare(strict_types=1);
namespace Proto\Database\Seeders;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Only seed if table is empty
        if (!$this->isEmpty('users')) {
            return;
        }

        $users = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            // More users...
        ];

        $this->insert('users', $users);
    }
}
```

### Running Seeders

#### Individual Seeder
```php
$seeder = new UserSeeder();
$seeder->run();
```

#### Using SeederManager
```php
use Proto\Database\Seeders\SeederManager;

$manager = new SeederManager();
$manager->run(UserSeeder::class);

// Or run multiple seeders
$manager->runMany([
    RoleSeeder::class,
    UserSeeder::class,
    ProductSeeder::class
]);
```

#### Using DatabaseSeeder
```php
$databaseSeeder = new DatabaseSeeder();
$databaseSeeder->run(); // Runs all configured seeders
```

## Seeder Base Class Methods

The `Seeder` base class provides several helpful methods:

### Database Operations

```php
// Insert data into a table
$this->insert('users', [
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com']
]);

// Truncate a table
$this->truncate('users');

// Check if table is empty
if ($this->isEmpty('users')) {
    // Seed data
}

// Get database connection
$db = $this->getConnection();
$db = $this->getConnection('testing'); // Specific connection
```

### Calling Other Seeders

```php
// Call another seeder
$this->call(RoleSeeder::class);

// Call multiple seeders
$this->callMany([
    RoleSeeder::class,
    UserSeeder::class
]);
```

## Seeder Examples

### User Seeder
```php
<?php declare(strict_types=1);
namespace Proto\Database\Seeders;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (!$this->isEmpty('users')) {
            return;
        }

        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'admin',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Regular User',
                'email' => 'user@example.com',
                'password' => password_hash('user123', PASSWORD_DEFAULT),
                'role' => 'user',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('users', $users);
    }
}
```

### Role Seeder
```php
<?php declare(strict_types=1);
namespace Proto\Database\Seeders;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        if (!$this->isEmpty('roles')) {
            return;
        }

        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'System administrator with full access',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'user',
                'display_name' => 'User',
                'description' => 'Regular user with basic access',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('roles', $roles);
    }
}
```

### Product Seeder with Relationships
```php
<?php declare(strict_types=1);
namespace Proto\Database\Seeders;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure categories exist first
        $this->call(CategorySeeder::class);

        if (!$this->isEmpty('products')) {
            return;
        }

        $products = [
            [
                'name' => 'Laptop Pro',
                'description' => 'High-performance laptop',
                'price' => 1299.99,
                'category_id' => 1,
                'stock' => 50,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Wireless Mouse',
                'description' => 'Ergonomic wireless mouse',
                'price' => 29.99,
                'category_id' => 2,
                'stock' => 100,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('products', $products);
    }
}
```

## Integration with Testing

### Using Seeders in Tests
```php
<?php declare(strict_types=1);
namespace Tests\Unit;

use Proto\Tests\Test;
use Proto\Database\Seeders\UserSeeder;
use Proto\Database\Seeders\RoleSeeder;

class UserTest extends Test
{
    // Seeders to run before each test
    protected array $seeders = [
        RoleSeeder::class,
        UserSeeder::class
    ];

    public function testUserCreation(): void
    {
        // Seeders have run automatically
        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('roles', 3);
    }
}
```

### Test-Specific Seeders
Create seeders specifically for testing in `src/Tests/Seeders/`:

```php
<?php declare(strict_types=1);
namespace Proto\Tests\Seeders;

use Proto\Database\Seeders\Seeder;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        $testUsers = [
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => password_hash('testpass', PASSWORD_DEFAULT),
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('users', $testUsers);
    }
}
```

## Advanced Usage

### Conditional Seeding
```php
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Different data for different environments
        $environment = $_ENV['APP_ENV'] ?? 'production';

        if ($environment === 'testing') {
            $this->seedTestUsers();
        } elseif ($environment === 'development') {
            $this->seedDevelopmentUsers();
        } else {
            $this->seedProductionUsers();
        }
    }

    private function seedTestUsers(): void
    {
        // Test user data
    }

    private function seedDevelopmentUsers(): void
    {
        // Development user data
    }

    private function seedProductionUsers(): void
    {
        // Production user data (minimal)
    }
}
```

### Using Faker for Random Data
```php
class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (!$this->isEmpty('users')) {
            return;
        }

        $users = [];
        for ($i = 0; $i < 50; $i++) {
            $users[] = [
                'name' => $this->faker()->name(),
                'email' => $this->faker()->email(),
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'status' => $this->faker()->boolean(80) ? 'active' : 'inactive',
                'created_at' => $this->faker()->dateTimeBetween('-1 year', 'now'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }

        $this->insert('users', $users);
    }

    private function faker()
    {
        // You could integrate with Proto's SimpleFaker or another faker library
        return new \Proto\Tests\SimpleFaker();
    }
}
```

## Best Practices

1. **Check if data exists** - Use `isEmpty()` to avoid duplicate data
2. **Order matters** - Run seeders in correct order (roles before users, categories before products)
3. **Use relationships** - Call dependent seeders using `call()` method
4. **Environment awareness** - Different data for different environments
5. **Keep it simple** - Focus on essential data for the seeder's purpose
6. **Use consistent timestamps** - Include created_at/updated_at fields
7. **Hash passwords** - Always hash passwords in user seeders
8. **Clean data** - Ensure data integrity and validation

## File Structure

```
src/
├── Database/
│   └── Seeders/
│       ├── Seeder.php              # Abstract base class
│       ├── SeederManager.php       # Manages seeder execution
│       ├── DatabaseSeeder.php      # Main seeder runner
│       ├── UserSeeder.php          # User data seeder
│       ├── RoleSeeder.php          # Role data seeder
│       └── ...                     # Other seeders
└── Tests/
    └── Seeders/
        ├── TestUserSeeder.php      # Test-specific user seeder
        └── ...                     # Other test seeders
```

The seeder system provides a clean, organized way to populate your Proto application's database with consistent, reliable data for development, testing, and initial deployment scenarios.