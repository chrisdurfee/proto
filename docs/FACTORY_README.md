# Proto Model Factory System

A complete factory system for generating model instances with fake data in tests and seeders.

## Features

✨ **Fluent API** - Chain methods for readable test setup
🎲 **Fake Data** - Integrates with SimpleFaker for realistic test data
🎯 **States** - Define named variations (admin, inactive, etc.)
🔄 **Sequences** - Create models with sequential attributes
🪝 **Callbacks** - Run code after making or creating models
🧪 **Test Integration** - Built-in helpers for PHPUnit tests
📦 **Zero Dependencies** - Works with existing Proto models

## Quick Start

### 1. Add trait to your model

```php
use Proto\Models\Model;
use Proto\Models\HasFactory;

class User extends Model
{
    use HasFactory;
}
```

### 2. Create a factory

```php
use Proto\Models\Factory;

class UserFactory extends Factory
{
    protected function model(): string
    {
        return User::class;
    }

    public function definition(): array
    {
        return [
            'name' => $this->faker()->name(),
            'email' => $this->faker()->email(),
            'status' => 'active'
        ];
    }

    public function stateAdmin(): array
    {
        return ['role' => 'admin'];
    }
}
```

### 3. Use in tests

```php
// Create a user
$user = User::factory()->create();

// Create an admin
$admin = User::factory()->state('admin')->create();

// Create 10 users
$users = User::factory()->count(10)->create();

// Create with custom attributes
$user = User::factory()->create(['email' => 'test@example.com']);
```

## Documentation

- **[Full Documentation](./FACTORIES.md)** - Complete guide with examples
- **[Quick Reference](./FACTORY_QUICK_REFERENCE.md)** - Cheat sheet for common tasks

## Files Added

```
src/Models/
├── Factory.php              # Base factory class
└── HasFactory.php           # Trait for models

src/Tests/Examples/
├── User.php                 # Example model
├── UserFactory.php          # Example factory

src/Tests/Unit/
└── FactoryExampleTest.php   # Complete test examples

src/Tests/Traits/
└── ModelTestHelpers.php     # Updated with factory() helper

docs/
├── FACTORIES.md             # Full documentation
└── FACTORY_QUICK_REFERENCE.md  # Quick reference guide
```

## Usage Examples

### Basic Creation

```php
// Create and save to database
$user = User::factory()->create();

// Create without saving
$user = User::factory()->make();

// Create multiple
$users = User::factory()->count(5)->create();
```

### Using States

```php
// Single state
$admin = User::factory()->state('admin')->create();

// Multiple states
$user = User::factory()
    ->state('admin')
    ->state('verified')
    ->create();

// State with parameters
$user = User::factory()
    ->state('withDomain', 'company.com')
    ->create();
```

### In Tests

```php
class UserTest extends Test
{
    public function testUserCreation()
    {
        $user = $this->factory(User::class)->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->id);
    }

    public function testAdminPermissions()
    {
        $admin = User::factory()->state('admin')->create();
        $user = User::factory()->create();

        $this->assertTrue($admin->hasPermission('manage_users'));
        $this->assertFalse($user->hasPermission('manage_users'));
    }
}
```

### Database Seeding

```php
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Regular users
        User::factory()->count(50)->create();

        // Admin users
        User::factory()->count(3)->state('admin')->create();

        // Test user
        User::factory()->create([
            'email' => 'admin@example.com'
        ]);
    }
}
```

## API Reference

### Factory Methods

| Method | Description |
|--------|-------------|
| `create(array $attrs = [])` | Create and save model(s) |
| `make(array $attrs = [])` | Create model(s) without saving |
| `raw(array $attrs = [])` | Get raw attribute arrays |
| `count(int $count)` | Set number of models to create |
| `state(string\|callable $state)` | Apply a state transformation |
| `set(array $attrs)` | Override attributes |
| `afterMaking(callable $callback)` | Run after making (before saving) |
| `afterCreating(callable $callback)` | Run after creating (after saving) |
| `sequence(callable $callback)` | Create with sequential attributes |

### Model Method

| Method | Description |
|--------|-------------|
| `Model::factory(int $count, array $attrs)` | Create factory instance |

### Test Helpers

| Method | Description |
|--------|-------------|
| `$this->factory(string $class, int $count, array $attrs)` | Create factory in tests |

## Why Use Factories?

### ❌ Without Factories

```php
public function testUserDashboard()
{
    // Verbose, error-prone
    $user = new User((object)[
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'status' => 'active',
        'role' => 'user',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    $user->add();

    // Repeat for every test...
}
```

### ✅ With Factories

```php
public function testUserDashboard()
{
    // Clean, reusable, maintainable
    $user = User::factory()->create();

    // Test logic...
}
```

## Best Practices

1. **Keep definitions simple** - Use states for variations
2. **One factory per model** - Clear separation of concerns
3. **Use named states** - `state('admin')` not inline attributes
4. **Leverage callbacks** - For relationships and complex setup
5. **Test with factories** - Faster, more maintainable tests

## Examples in Action

See `src/Tests/Unit/FactoryExampleTest.php` for:

- ✅ Basic factory usage
- ✅ States and parameters
- ✅ Sequences and callbacks
- ✅ Raw attributes
- ✅ Real-world testing patterns
- ✅ All available methods

Run the examples:

```bash
composer test -- --filter=FactoryExampleTest
```

## Integration with Proto

Factories work seamlessly with Proto's:

- ✅ Model system (`Proto\Models\Model`)
- ✅ Test framework (`Proto\Tests\Test`)
- ✅ SimpleFaker (`Proto\Tests\SimpleFaker`)
- ✅ Database layer (transactions, connections)
- ✅ Seeders (`Proto\Database\Seeders\Seeder`)

## License

Part of the Proto Framework - Same license as Proto.

## Contributing

When adding new features:

1. Add to `Factory.php` base class
2. Update `FACTORIES.md` documentation
3. Add examples to `FactoryExampleTest.php`
4. Update this README

---

**Made with ❤️ for Proto Framework**
