# Factory System Quick Reference

## Installation

### 1. Add HasFactory trait to your model

```php
use Proto\Models\Model;
use Proto\Models\HasFactory;

class User extends Model
{
    use HasFactory;
}
```

### 2. Create a factory class

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
            'email' => $this->faker()->email()
        ];
    }
}
```

---

## Cheat Sheet

### Create Models

```php
// Single model (saved to DB)
User::factory()->create();

// Single model (not saved)
User::factory()->make();

// Multiple models
User::factory()->count(5)->create();
User::factory(5)->create();  // Alternative

// With attributes
User::factory()->create(['name' => 'John']);
User::factory()->set(['name' => 'John'])->create();
```

### States

```php
// Define in factory
public function stateAdmin(): array
{
    return ['role' => 'admin'];
}

// Use in code
User::factory()->state('admin')->create();

// State with parameters
public function stateWithDomain(string $domain): array
{
    return ['email' => "user@{$domain}"];
}
User::factory()->state('withDomain', 'company.com')->create();

// Multiple states
User::factory()->state('admin')->state('verified')->create();

// Callable state
User::factory()->state(fn($attrs) => ['name' => strtoupper($attrs['name'])])->create();
```

### Advanced Usage

```php
// Raw attributes (no model)
$attributes = User::factory()->raw();

// Sequences
User::factory()->count(10)->sequence(fn($i) => ['name' => "User {$i}"]);

// Callbacks
User::factory()
    ->afterMaking(fn($user) => $user->set('verified', true))
    ->afterCreating(fn($user) => Profile::factory()->create(['user_id' => $user->id]))
    ->create();

// Static methods
UserFactory::new()->create();
UserFactory::times(5)->create();
```

### In Tests

```php
use Proto\Tests\Test;

class MyTest extends Test
{
    public function testExample()
    {
        // Using helper
        $user = $this->factory(User::class)->create();

        // Direct usage
        $admin = User::factory()->state('admin')->create();

        // Multiple with state
        $users = User::factory()->count(10)->state('active')->create();
    }
}
```

---

## Common Patterns

### Testing User Authentication

```php
public function testLoginRedirectsToDashboard()
{
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password')
    ]);

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertSuccessful();
}
```

### Testing Permissions

```php
public function testAdminCanDeleteUsers()
{
    $admin = User::factory()->state('admin')->create();
    $user = User::factory()->create();

    $response = $this->actingAs($admin)->delete("/users/{$user->id}");
    $response->assertSuccessful();
}
```

### Testing Relationships

```php
public function testUserHasPosts()
{
    $user = User::factory()
        ->afterCreating(function ($user) {
            Post::factory()->count(5)->create(['user_id' => $user->id]);
        })
        ->create();

    $this->assertCount(5, $user->posts);
}
```

### Seeding Database

```php
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Regular users
        User::factory()->count(50)->create();

        // Admin users
        User::factory()->count(3)->state('admin')->create();

        // Specific test user
        User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin'
        ]);
    }
}
```

---

## Faker Methods

```php
$faker->name()                      // Full name
$faker->firstName()                 // First name only
$faker->lastName()                  // Last name only
$faker->email()                     // Email address
$faker->phoneNumber()               // Phone number
$faker->address()                   // Street address
$faker->city()                      // City name
$faker->text(10)                    // 10 words of text
$faker->numberBetween(1, 100)       // Random integer
$faker->floatBetween(0, 100, 2)     // Random float
$faker->boolean(50)                 // Boolean (50% true)
$faker->dateTimeBetween('-1 year')  // Random date
$faker->uuid()                      // UUID string
```

---

## Tips

1. **Keep definitions simple** - Use states for variations
2. **One factory per model** - Don't share factories
3. **Use callbacks for relationships** - Clean and explicit
4. **Name states clearly** - `stateAdmin()` not `state1()`
5. **Test with factories** - Faster and more maintainable

---

## Troubleshooting

### "Factory class not found"

Make sure your factory is in one of these locations:
- `ModelClass` + `Factory` suffix in same namespace
- `ModelClass\Factories\ModelClassFactory`
- Or override `factoryClass()` in your model

### "Failed to create model"

Check that your model's `add()` method works and database table exists.

### State method not found

State methods must be named `state` + PascalCase name:
```php
// Wrong
public function admin() { ... }

// Correct
public function stateAdmin() { ... }
```

---

## Full Example

```php
// Model: Modules/Blog/Models/Post.php
class Post extends Model
{
    use HasFactory;
    protected static ?string $tableName = 'posts';
}

// Factory: Modules/Blog/Models/PostFactory.php
class PostFactory extends Factory
{
    protected function model(): string { return Post::class; }

    public function definition(): array
    {
        return [
            'title' => $this->faker()->text(5),
            'content' => $this->faker()->text(50),
            'status' => 'draft'
        ];
    }

    public function statePublished(): array
    {
        return ['status' => 'published'];
    }
}

// Test: src/Tests/Unit/PostTest.php
class PostTest extends Test
{
    public function testCreatePost()
    {
        $post = Post::factory()->state('published')->create();
        $this->assertEquals('published', $post->status);
    }
}
```
