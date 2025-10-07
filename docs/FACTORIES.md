# Proto Model Factories

Model factories provide a convenient way to generate fake data for your models during testing and development. They integrate seamlessly with Proto's Model system and SimpleFaker.

## Table of Contents

- [Basic Usage](#basic-usage)
- [Creating Factories](#creating-factories)
- [Factory States](#factory-states)
- [Advanced Features](#advanced-features)
- [Testing with Factories](#testing-with-factories)
- [Best Practices](#best-practices)

---

## Basic Usage

```php
<?php
namespace Modules\User\Models;

use Proto\Models\Model;

class User extends Model
{
    protected static ?string $tableName = 'users';
    protected static array $fields = ['id', 'name', 'email', 'status'];
}
```

### Creating Model Instances

```php
// Create a single user and save to database
$user = User::factory()->create();

// Create without saving (make)
$user = User::factory()->make();

// Create with specific attributes
$user = User::factory()->create([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Create multiple users
$users = User::factory()->count(5)->create();

// Alternative syntax
$users = User::factory(5)->create();
```

---

## Creating Factories

### Factory File Structure

Create a factory class that extends `Proto\Models\Factory`:

```php
<?php
namespace Modules\User\Factories;

use Proto\Models\Factory;
use Modules\User\Models\User;

class UserFactory extends Factory
{
    /**
     * Get the model class.
     */
    protected function model(): string
    {
        return User::class;
    }

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $faker = $this->faker();

        return [
            'name' => $faker->name(),
            'email' => $faker->email(),
            'password' => password_hash('password', PASSWORD_BCRYPT),
            'status' => 'active',
            'created_at' => $faker->dateTimeBetween('-1 year', 'now')
        ];
    }
}
```

### Factory Class Location

Factories can be located:

1. **Same namespace as model**: `Modules\User\Models\UserFactory`
2. **Factories subfolder**: `Modules\User\Models\Factories\UserFactory`
3. **Custom location** (override `factoryClass()` in model):

```php
class User extends Model
{
    use HasFactory;

    protected static function factoryClass(): string
    {
        return \Custom\Location\UserFactory::class;
    }
}
```

---

## Factory States

States allow you to define variations of your model.

### Defining States

```php
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker()->name(),
            'email' => $this->faker()->email(),
            'role' => 'user',
            'status' => 'active'
        ];
    }

    /**
     * State: Admin user
     */
    public function stateAdmin(): array
    {
        return [
            'role' => 'admin',
            'status' => 'active'
        ];
    }

    /**
     * State: Inactive user
     */
    public function stateInactive(): array
    {
        return [
            'status' => 'inactive'
        ];
    }

    /**
     * State: Suspended user
     */
    public function stateSuspended(): array
    {
        return [
            'status' => 'suspended',
            'suspended_at' => $this->faker()->dateTimeBetween('-30 days', 'now')
        ];
    }

    /**
     * State: User with custom domain
     */
    public function stateWithDomain(string $domain): array
    {
        $faker = $this->faker();
        $username = strtolower($faker->firstName() . '.' . $faker->lastName());

        return [
            'email' => $username . '@' . $domain
        ];
    }
}
```

### Using States

```php
// Apply a single state
$admin = User::factory()->state('admin')->create();

// State with parameters
$user = User::factory()->state('withDomain', 'company.com')->create();

// Chain multiple states
$user = User::factory()
    ->state('admin')
    ->state('suspended')
    ->create();

// Apply state to multiple models
$admins = User::factory()->count(5)->state('admin')->create();
```

### Callable States

Use closures for dynamic state logic:

```php
$user = User::factory()
    ->state(function ($attributes) {
        return [
            'name' => strtoupper($attributes['name']),
            'email' => str_replace('@', '+test@', $attributes['email'])
        ];
    })
    ->create();
```

---

## Advanced Features

### Raw Attributes

Get attribute arrays without creating model instances:

```php
// Single attribute array
$attributes = User::factory()->raw();
// ['name' => 'John Doe', 'email' => 'john@example.com', ...]

// Multiple attribute arrays
$attributes = User::factory()->count(5)->raw();
// [
//   ['name' => 'John Doe', ...],
//   ['name' => 'Jane Smith', ...],
//   ...
// ]
```

### Sequences

Create models with sequential or alternating attributes:

```php
// Sequential values
$users = User::factory()
    ->count(10)
    ->sequence(function ($index) {
        return [
            'name' => "User {$index}",
            'email' => "user{$index}@example.com"
        ];
    });

// Alternating values
$users = User::factory()
    ->count(10)
    ->sequence(function ($index) {
        return [
            'role' => $index % 2 === 0 ? 'admin' : 'user'
        ];
    });
```

### Callbacks

Execute code after making or creating models:

```php
$user = User::factory()
    ->afterMaking(function ($user) {
        // Runs after model is instantiated, before saving
        $user->set('name', strtoupper($user->name));
    })
    ->afterCreating(function ($user) {
        // Runs after model is saved to database
        // Example: Create related records
        Profile::factory()->create(['user_id' => $user->id]);
    })
    ->create();
```

### Set Method

Override multiple attributes at once:

```php
$user = User::factory()
    ->set([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'role' => 'moderator'
    ])
    ->create();
```

### Static Factory Methods

```php
// Create factory with new()
$user = UserFactory::new()->create();

// Create multiple with times()
$users = UserFactory::times(5)->create();

// With attributes
$users = UserFactory::new(3, ['status' => 'active'])->create();
```

---

## Testing with Factories

### In PHPUnit Tests

```php
use Proto\Tests\Test;
use Modules\User\Models\User;

class UserTest extends Test
{
    public function testUserCreation(): void
    {
        // Use factory helper
        $user = $this->factory(User::class)->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->id);
    }

    public function testAdminPermissions(): void
    {
        $admin = User::factory()->state('admin')->create();
        $user = User::factory()->create();

        $this->assertTrue($admin->hasPermission('manage_users'));
        $this->assertFalse($user->hasPermission('manage_users'));
    }

    public function testUserStatusTransitions(): void
    {
        // Create inactive user
        $user = User::factory()->state('inactive')->create();

        $this->assertEquals('inactive', $user->status);

        // Test activation
        $user->activate();

        $this->assertEquals('active', $user->status);
    }
}
```

### Real-World Testing Scenario

```php
public function testUserDashboardShowsCorrectData(): void
{
    // Setup: Create test data
    $user = User::factory()->create([
        'email' => 'test@example.com'
    ]);

    $posts = Post::factory()
        ->count(5)
        ->create(['user_id' => $user->id]);

    $comments = Comment::factory()
        ->count(10)
        ->create(['user_id' => $user->id]);

    // Act: Get dashboard data
    $response = $this->actingAs($user)
        ->getJson('/api/dashboard');

    // Assert: Verify response
    $response->assertStatus(200);
    $response->assertJson([
        'posts_count' => 5,
        'comments_count' => 10,
        'user' => [
            'email' => 'test@example.com'
        ]
    ]);
}
```

---

## Best Practices

### 1. Keep Factories Simple

```php
// Good: Simple, reusable defaults
public function definition(): array
{
    return [
        'name' => $this->faker()->name(),
        'email' => $this->faker()->email(),
        'status' => 'active'
    ];
}

// Avoid: Complex logic in definition
public function definition(): array
{
    $name = $this->faker()->name();
    $slug = str_replace(' ', '-', strtolower($name));
    $randomStatus = ['active', 'inactive'][rand(0, 1)];
    // ... too complex
}
```

### 2. Use States for Variations

```php
// Good: Clear, named states
User::factory()->state('admin')->create();
User::factory()->state('suspended')->create();

// Avoid: Overriding everything inline
User::factory()->create([
    'role' => 'admin',
    'permissions' => ['manage_users', 'manage_posts'],
    'status' => 'active',
    'verified' => true
    // ... too much inline customization
]);
```

### 3. Use Relationships Properly

```php
// Good: Create related models in afterCreating
class UserFactory extends Factory
{
    public function withProfile(): static
    {
        return $this->afterCreating(function ($user) {
            Profile::factory()->create(['user_id' => $user->id]);
        });
    }
}

// Usage
$user = User::factory()->withProfile()->create();
```

### 4. One Factory Per Model

```php
// Good: Dedicated factory file
// Modules/User/Models/Factories/UserFactory.php
class UserFactory extends Factory { ... }

// Modules/Post/Models/Factories/PostFactory.php
class PostFactory extends Factory { ... }
```

### 5. Use Faker Methods Consistently

```php
public function definition(): array
{
    $faker = $this->faker();

    return [
        'name' => $faker->name(),           // Good: Realistic
        'email' => $faker->email(),         // Good: Realistic
        'phone' => $faker->phoneNumber(),   // Good: Realistic
        'age' => $faker->numberBetween(18, 80), // Good: Realistic range
    ];
}
```

---

## Available Faker Methods

Proto's `SimpleFaker` provides these methods:

```php
$faker->name()                      // "John Smith"
$faker->firstName()                 // "John"
$faker->lastName()                  // "Smith"
$faker->email()                     // "john.smith@example.com"
$faker->phoneNumber()               // "(555) 123-4567"
$faker->address()                   // "123 Main St"
$faker->city()                      // "Springfield"
$faker->text(20)                    // 20 words of lorem ipsum
$faker->numberBetween(1, 100)       // Random integer
$faker->floatBetween(0.0, 100.0, 2) // Random float with 2 decimals
$faker->boolean(70)                 // true with 70% probability
$faker->dateTimeBetween('-1 year', 'now') // Random date
$faker->uuid()                      // "550e8400-e29b-41d4-a716-446655440000"
```

---

## Complete Example

```php
<?php
namespace Modules\Blog\Models\Factories;

use Proto\Models\Factory;
use Modules\Blog\Models\Post;

class PostFactory extends Factory
{
    protected function model(): string
    {
        return Post::class;
    }

    public function definition(): array
    {
        $faker = $this->faker();

        return [
            'title' => $faker->text(5),
            'slug' => strtolower(str_replace(' ', '-', $faker->text(5))),
            'content' => $faker->text(50),
            'status' => 'draft',
            'views' => 0,
            'published_at' => null,
            'created_at' => $faker->dateTimeBetween('-1 year', 'now')
        ];
    }

    public function statePublished(): array
    {
        return [
            'status' => 'published',
            'published_at' => $this->faker()->dateTimeBetween('-6 months', 'now')
        ];
    }

    public function stateDraft(): array
    {
        return ['status' => 'draft'];
    }

    public function stateFeatured(): array
    {
        return [
            'is_featured' => true,
            'views' => $this->faker()->numberBetween(1000, 10000)
        ];
    }

    public function withAuthor(int $userId): static
    {
        return $this->set(['user_id' => $userId]);
    }
}

// Usage
$post = Post::factory()->state('published')->create();
$featuredPosts = Post::factory()->count(5)->state('featured')->create();
$draftWithAuthor = Post::factory()
    ->state('draft')
    ->withAuthor(1)
    ->create();
```

---

## Summary

Factories provide:

✅ **Consistent test data** - Predictable, realistic fake data
✅ **Readable tests** - Clear intent with named states
✅ **Fast setup** - Create complex test scenarios quickly
✅ **Maintainable** - Centralized model creation logic
✅ **Flexible** - States, sequences, callbacks for any scenario

Use factories whenever you need model instances in tests or seeders!
