# Proto Testing Quick Reference

Quick reference for common testing scenarios in the Proto framework.

## TL;DR - Most Important Rules

### ✅ DO THIS (Framework v2.0+)
```php
class MyTest extends Test
{
    public function testUpdate(): void
    {
        $model = Model::factory()->create();
        $model->name = 'Updated';
        $model->update();

        // ✅ Now transaction-safe! Framework fixed this.
        $updated = Model::getById($model->id);

        // ✅ Also works
        $updated = Model::getBy(['id' => $model->id]);

        // ✅ Or refresh the existing instance
        $model->refresh();
    }
}
```

### ❌ DON'T DO THIS
```php
class MyTest extends Test
{
    protected function setUp(): void
    {
        parent::setUp();
        // ❌ Don't disable foreign keys
        $this->getTestDatabase()->execute('SET FOREIGN_KEY_CHECKS=0');
    }

    public function testUpdate(): void
    {
        $model = Model::factory()->create();
        $model->update();

        // ❌ Don't use getById() in tests (causes lockups)
        $updated = Model::getById($model->id);
    }
}
```

---

## Basic Test Structure

```php
<?php declare(strict_types=1);
namespace Modules\MyModule\Tests\Feature;

use Proto\Tests\Test;
use Modules\MyModule\Models\MyModel;

class MyModelTest extends Test
{
    // No setUp/tearDown needed!
    // Transactions are automatic

    public function testCreate(): void
    {
        $model = MyModel::factory()->create();

        $this->assertNotNull($model->id);
    }
}
```

---

## Common Test Patterns

### Creating Test Data

```php
// Single record
$user = User::factory()->create();

// Multiple records
$users = User::factory()->count(5)->create();

// With specific attributes
$user = User::factory()->create([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// With states
$admin = User::factory()->state('admin')->create();

// Without persisting (make instead of create)
$user = User::factory()->make();

// Just get raw attributes
$attributes = User::factory()->raw();
```

### Updating Records

```php
public function testUpdate(): void
{
    // Create
    $user = User::factory()->create(['name' => 'Original']);

    // Update
    $user->name = 'Updated';
    $result = $user->update();

    // Verify
    $this->assertTrue($result);

    // ✅ Fetch with getBy() - avoids lockups
    $updated = User::getBy(['id' => $user->id]);
    $this->assertEquals('Updated', $updated->name);

    // ✅ Or use database assertion
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated'
    ]);
}
```

### Deleting Records

```php
public function testDelete(): void
{
    $user = User::factory()->create();
    $userId = $user->id;

    $result = $user->delete();
    $this->assertTrue($result);

    // ✅ Use getBy() not getById()
    $deleted = User::getBy(['id' => $userId]);
    $this->assertNull($deleted);

    // ✅ Or use database assertion
    $this->assertDatabaseMissing('users', ['id' => $userId]);
}
```

### Testing Relationships

```php
public function testRelationship(): void
{
    // Create parent
    $user = User::factory()->create();

    // Create child with relationship
    $post = Post::factory()->create([
        'user_id' => $user->id
    ]);

    // Verify
    $this->assertEquals($user->id, $post->user_id);

    // ✅ Fetch and verify relationship
    $foundPost = Post::getBy(['id' => $post->id]);
    $this->assertEquals($user->id, $foundPost->user_id);
}
```

### Testing with Seeders

```php
class MyTest extends Test
{
    protected array $seeders = [
        UserSeeder::class,
        RoleSeeder::class
    ];

    public function testWithSeededData(): void
    {
        // Seeded data is available
        $admin = Role::getBy(['name' => 'Admin']);
        $this->assertNotNull($admin);
    }
}
```

---

## Database Assertions

```php
// Assert record exists
$this->assertDatabaseHas('users', [
    'email' => 'test@example.com',
    'status' => 'active'
]);

// Assert record doesn't exist
$this->assertDatabaseMissing('users', [
    'email' => 'deleted@example.com'
]);

// Assert record count
$this->assertDatabaseCount('users', 5);
```

---

## HTTP Testing

```php
public function testApiEndpoint(): void
{
    // GET request
    $response = $this->getJson('/api/users');
    $response->assertStatus(200);
    $response->assertJsonStructure(['data', 'message']);

    // POST request
    $response = $this->postJson('/api/users', [
        'name' => 'John',
        'email' => 'john@example.com'
    ]);
    $response->assertStatus(201);

    // Authenticated request
    $user = User::factory()->create();
    $response = $this->actingAs($user)
                     ->getJson('/api/profile');
    $response->assertStatus(200);
}
```

---

## Transaction Isolation (How it Works)

```php
// Test 1
public function testCreateUser(): void
{
    // START TRANSACTION (automatic)

    $user = User::factory()->create();
    $this->assertNotNull($user->id);

    // ROLLBACK (automatic)
    // User is removed from database
}

// Test 2 - Completely isolated!
public function testCreatePost(): void
{
    // START TRANSACTION (automatic)
    // Database is clean - no user from Test 1

    $count = User::count();
    $this->assertEquals(0, $count); // No users!

    $post = Post::factory()->create();

    // ROLLBACK (automatic)
}
```

---

## Critical: Avoiding Lockups

### The Problem
```php
// ⚠️ This WILL lock up or timeout!
public function testUpdate(): void
{
    $user = User::factory()->create();
    $user->name = 'Updated';
    $user->update();

    // Database lock! Test hangs here
    $updated = User::getById($user->id);
}
```

### The Solution
```php
// ✅ This works perfectly!
public function testUpdate(): void
{
    $user = User::factory()->create();
    $user->name = 'Updated';
    $user->update();

    // No lock - uses same transaction
    $updated = User::getBy(['id' => $user->id]);
}
```

### Why?
- `getById()` → new connection → can't see uncommitted changes → lock
- `getBy()` → same connection → sees uncommitted changes → works

### Quick Rules
| Method | In Tests? | Why |
|--------|-----------|-----|
| `Model::get($id)` | ✅ Use | Proto built-in, same transaction |
| `Model::getBy(['field' => $value])` | ✅ Use | Proto built-in, same transaction |
| `Model::fetchWhere([...])` | ✅ Use | Same transaction |
| `$model->refresh()` | ✅ Use | Same transaction |
| Custom static methods | ❌ Avoid | May use different connection |

---

## Disabling Transactions (Rarely Needed)

```php
class SpecialTest extends Test
{
    // Disable automatic transactions
    protected bool $useTransactions = false;

    protected function tearDown(): void
    {
        // Must manually clean up!
        User::query()->delete();
        Post::query()->delete();

        parent::tearDown();
    }

    public function testSomething(): void
    {
        // Test code here
    }
}
```

**Only disable transactions if:**
- Testing transaction behavior itself
- Testing full-text search indexes
- Testing database triggers/stored procedures
- Integration tests requiring committed data

**99% of tests should use transactions!**

---

## Common Mistakes

### ❌ Mistake 1: Disabling Foreign Keys
```php
// DON'T DO THIS
protected function setUp(): void
{
    parent::setUp();
    $this->getTestDatabase()->execute('SET FOREIGN_KEY_CHECKS=0');
}
```
**Why it's wrong:** Defeats referential integrity, masks bugs, unnecessary

**Do this instead:** Use transactions (automatic) and create proper test data

### ❌ Mistake 2: Implementing Custom Static Methods
```php
// DON'T IMPLEMENT THIS IN YOUR MODELS
public static function getById($id)
{
    $db = self::getConnection(); // New connection!
    return $db->query("SELECT * FROM table WHERE id = ?", [$id]);
}

public static function findBySlug($slug)
{
    $db = static::getConnection(); // New connection!
    return $db->query("SELECT * FROM table WHERE slug = ?", [$slug]);
}
```
**Why it's wrong:** Different connection, can't see uncommitted changes, causes locks

**Do this instead:** Use Proto's built-in methods:
- `Model::get($id)` (by ID)
- `Model::getBy(['slug' => $value])` (by any field)
- `Model::fetchWhere([...])` (multiple records)

### ❌ Mistake 3: Manual Cleanup
```php
// DON'T DO THIS
protected function tearDown(): void
{
    User::query()->delete();
    Post::query()->delete();
    Comment::query()->delete();
    parent::tearDown();
}
```
**Why it's wrong:** Slow, error-prone, unnecessary

**Do this instead:** Use transactions (automatic rollback)

### ❌ Mistake 4: Committing in Tests
```php
// DON'T DO THIS
public function testSomething(): void
{
    $user = User::factory()->create();
    $this->getTestDatabase()->execute('COMMIT');
    // ...
}
```
**Why it's wrong:** Breaks test isolation, data persists between tests

**Do this instead:** Let the framework handle transactions

---

## Debugging Tests

### Check if transactions are enabled
```php
public function testTransactionsEnabled(): void
{
    $this->assertTrue($this->useTransactions);
}
```

### Check if database connection is working
```php
public function testDatabaseConnection(): void
{
    $db = $this->getTestDatabase();
    $result = $db->execute('SELECT 1 as test');
    $this->assertNotNull($result);
}
```

### Check test isolation
```php
public function testA(): void
{
    User::factory()->create(['email' => 'test@example.com']);
    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
}

public function testB(): void
{
    // This should be 0 if isolation works
    $count = User::count();
    $this->assertEquals(0, $count);
}
```

---

## Performance Tips

### ✅ Fast: Use Transactions
```php
public function testCreateManyUsers(): void
{
    // Creates 1000 users
    User::factory()->count(1000)->create();

    // Instant rollback - no matter how many records!
    // Takes milliseconds
}
```

### ❌ Slow: Manual Deletion
```php
protected function tearDown(): void
{
    // Deletes 1000 users one by one
    User::query()->delete();

    // Takes seconds - very slow!
    parent::tearDown();
}
```

---

## See Also

- [TESTING_BEST_PRACTICES.md](TESTING_BEST_PRACTICES.md) - Comprehensive guide
- [FACTORIES.md](FACTORIES.md) - Factory usage
- [examples/PermissionTest.example.php](examples/PermissionTest.example.php) - Complete example

---

## Need Help?

**Test locks up?** → Use `getBy()` instead of `getById()`

**Tests not isolated?** → Make sure `useTransactions = true` (default)

**Foreign key errors?** → Create parent records first, use proper relationships

**Slow tests?** → Use transactions (should be automatic), avoid manual cleanup
