# Testing Best Practices for Proto Framework

## Database Test Isolation

### ✅ The Right Way: Use Transactions (Default)

The Proto framework automatically handles test isolation using database transactions. **You don't need to do anything special!**

```php
<?php declare(strict_types=1);
namespace Modules\User\Tests\Feature;

use Proto\Tests\Test;
use Modules\User\Models\Permission;

class PermissionTest extends Test
{
    // That's it! No setUp/tearDown needed for basic tests

    public function testCreatePermission(): void
    {
        $permission = Permission::factory()->create();

        $this->assertNotNull($permission->id);
        // This data will be automatically rolled back after the test
    }
}
```

### How It Works

1. **Before each test**: Framework starts a database transaction
2. **During test**: All database changes happen within the transaction
3. **After test**: Framework rolls back the transaction (all changes disappear)

**Benefits:**
- ✅ Tests are completely isolated
- ✅ Foreign key constraints remain enforced
- ✅ No manual cleanup needed
- ✅ Fast (no truncate/delete operations)
- ✅ Database-agnostic (works with MySQL, PostgreSQL, etc.)

### ❌ Don't Do This (Anti-Pattern)

```php
// ❌ BAD: Don't disable foreign key checks
protected function setUp(): void
{
    parent::setUp();
    $this->getTestDatabase()->execute('SET FOREIGN_KEY_CHECKS=0');
}

protected function tearDown(): void
{
    $this->getTestDatabase()->execute('SET FOREIGN_KEY_CHECKS=1');
    parent::tearDown();
}
```

**Why it's bad:**
- Defeats the purpose of having foreign keys
- MySQL-specific (won't work with other databases)
- Masks data integrity issues
- Can hide bugs in your application code
- Unnecessary when using transactions

### Advanced: Disabling Transactions

In rare cases where you need to test transaction behavior itself or use features incompatible with transactions, you can disable transaction isolation:

```php
<?php
class MySpecialTest extends Test
{
    protected bool $useTransactions = false;

    protected function tearDown(): void
    {
        // Manual cleanup required when not using transactions
        Permission::query()->delete();
        Role::query()->delete();

        parent::tearDown();
    }
}
```

**When to disable transactions:**
- Testing transaction-specific behavior (commits, rollbacks)
- Testing full-text search indexes (some require committed data)
- Testing database-level triggers or stored procedures
- Integration tests that verify cross-connection behavior

### Using Seeders

You can seed data that will be automatically rolled back:

```php
<?php
class PermissionTest extends Test
{
    protected array $seeders = [
        RoleSeeder::class,
        PermissionSeeder::class,
    ];

    public function testPermissionWithRoles(): void
    {
        // Seeded data is available here
        $role = Role::getBy(['name' => 'Admin']);
        $this->assertNotNull($role);

        // All seeded data will be rolled back after test
    }
}
```

### Database Assertions

Use built-in assertions to verify database state:

```php
public function testCreatePermission(): void
{
    $permission = Permission::factory()->create([
        'slug' => 'test-permission'
    ]);

    // Assert data exists in database
    $this->assertDatabaseHas('permissions', [
        'slug' => 'test-permission',
        'id' => $permission->id
    ]);

    // Assert data doesn't exist
    $this->assertDatabaseMissing('permissions', [
        'slug' => 'non-existent-permission'
    ]);

    // Assert count
    $this->assertDatabaseCount('permissions', 1);
}
```

### Testing Foreign Key Relationships

Transactions preserve foreign key constraints, so you can test them properly:

```php
public function testPermissionRequiresValidModule(): void
{
    // This should work (valid relationship)
    $permission = Permission::factory()->create([
        'module' => 'User'
    ]);
    $this->assertNotNull($permission->id);

    // If your schema enforces foreign keys, invalid data will fail
    // (which is what you want to test!)
}
```

### Performance Tips

**Fast**: Transactions are rolled back (very fast)
```php
public function testMultiplePermissions(): void
{
    Permission::factory()->count(100)->create();
    // Rollback is instant, no matter how much data
}
```

**Slow**: Manual deletion (avoid unless necessary)
```php
protected function tearDown(): void
{
    Permission::query()->delete(); // Slow if many records
    parent::tearDown();
}
```

### Testing Database Transactions in Your Code

If you need to test that your application code properly uses transactions:

```php
class TransactionTest extends Test
{
    // Keep transactions enabled
    protected bool $useTransactions = true;

    public function testUserServiceRollsBackOnError(): void
    {
        $initialCount = User::count();

        try {
            // Your service should use its own nested transaction
            $service = new UserService();
            $service->createUserWithProfile([/* data that causes error */]);
        } catch (\Exception $e) {
            // Expected exception
        }

        // Verify your service rolled back its changes
        $this->assertEquals($initialCount, User::count());

        // The test's outer transaction will still rollback everything
    }
}
```

### Common Issues

#### Issue: Test locks up or deadlocks during update/delete operations
**Cause**: Using `getById()` or other static methods that create new connections
**Solution**: Use instance-based queries like `getBy()` that use the same transaction

```php
// ❌ BAD: May cause lockups in tests
$permission->update();
$updated = Permission::getById($permission->id); // New connection!

// ✅ GOOD: Uses same transaction
$permission->update();
$updated = Permission::getBy(['id' => $permission->id]); // Same connection!
```

**Why this happens:**
- `getById()` may use a static/global connection
- This new connection can't see uncommitted changes from your test transaction
- Database waits for commit/rollback → deadlock or timeout
- `getBy()` uses the model's storage instance, which is bound to the test transaction

**Rule of thumb:** In tests, prefer `getBy(['id' => $id])` over `getById($id)`

#### Issue: Changes persist between tests
**Cause**: You may be using a different database connection
**Solution**: Ensure all models use the 'testing' connection (framework handles this automatically)

#### Issue: Foreign key constraint errors
**Cause**: You're trying to create data without required relationships
**Solution**: Create parent records first, or use factories with relationships

```php
// ✅ Good: Create dependencies first
$role = Role::factory()->create();
$permission = Permission::factory()->create([
    'role_id' => $role->id
]);

// Or better: Use factory relationships
$permission = Permission::factory()
    ->for(Role::factory())
    ->create();
```

#### Issue: Deadlocks or timeout errors
**Cause**: Using multiple database connections or persistent connections in tests
**Solution**: Framework handles this automatically by forcing single non-persistent connection for tests

## Troubleshooting

### My test locks up or times out!

**Symptom:** Test hangs when calling `update()` or `delete()` followed by fetching the record

**Root cause:** Transaction isolation + using custom static methods that create new connections

**Example of the problem (if you implemented custom `getById()`):**
```php
public function testUpdate(): void
{
    $permission = Permission::factory()->create();
    $permission->name = 'Updated';
    $permission->update();

    // ⚠️ This will lock up if getById() uses a new connection!
    $updated = Permission::getById($permission->id);
}
```

**Why it locks:**
1. Your test runs in a transaction (uncommitted changes)
2. Custom `getById()` implementation uses a different database connection
3. That connection can't see uncommitted changes (transaction isolation)
4. Database waits for the transaction to commit → timeout/deadlock

**Solution: Use Proto's built-in methods (all transaction-safe)**
```php
public function testUpdate(): void
{
    $permission = Permission::factory()->create();
    $permission->name = 'Updated';
    $permission->update();

    // ✅ All of these work! (use same transaction)
    $updated = Permission::get($permission->id);           // Standard method
    $updated = Permission::getBy(['id' => $permission->id]); // Explicit filter
    $permission->refresh();                                 // Reload instance
}
```

**Rule of thumb in tests:**
- ✅ Use `Model::get($id)` - Standard Proto method (transaction-safe)
- ✅ Use `Model::getBy(['id' => $id])` - Explicit filter (transaction-safe)
- ✅ Use `Model::fetchWhere([...])` - Uses same transaction
- ✅ Use `$model->refresh()` - Reloads from same transaction
- ❌ Avoid custom static methods that create new connections

### Why does Proto's get() work but custom static methods don't?

**Technical explanation:**

```php
// ❌ Custom static method (WRONG - if you implemented this):
public static function getById($id)
{
    // Creates a NEW connection (or uses a cached one)
    // This connection is NOT part of your test transaction
    $db = self::getConnection();
    return $db->query("SELECT * FROM table WHERE id = ?", [$id]);
}

// ✅ Proto's get() (CORRECT - built-in):
public static function get($id)
{
    $instance = new static();
    // Uses the model instance's storage
    // This storage is bound to your test transaction
    $row = $instance->storage->get($id);
    return ($row) ? new static($row) : null;
}

// ✅ Proto's getBy() (CORRECT - built-in):
public static function getBy($conditions)
{
    $instance = new static();
    // Uses the model instance's storage
    // This storage is bound to your test transaction
    return $instance->storage->getBy($conditions);
}
```

**In tests:**
- Test transaction: `START TRANSACTION` → changes → `ROLLBACK`
- Proto's `get()` and `getBy()`: See changes (same transaction) ✅
- Custom static methods with new connections: Can't see changes ❌
- Result: Use Proto's built-in methods, avoid custom implementations

### I'm getting foreign key constraint errors

**If you're getting constraint errors in tests, that's actually GOOD!**

It means:
- Your tests are finding real bugs
- Your foreign keys are working correctly
- Your data integrity is being enforced

**Solution:** Fix the test data, not the constraints

```php
// ❌ Bad: Disable constraints
$this->getTestDatabase()->execute('SET FOREIGN_KEY_CHECKS=0');

// ✅ Good: Create proper test data
$role = Role::factory()->create();
$permission = Permission::factory()->create([
    'role_id' => $role->id  // Proper relationship!
]);
```

### Summary

**Default behavior (use this 99% of the time):**
```php
class MyTest extends Test
{
    // Just extend Test - transactions are automatic!

    public function testSomething(): void
    {
        // Create test data
        $model = Model::factory()->create();

        // Update it
        $model->name = 'Updated';
        $model->update();

        // ✅ Fetch with getBy() - same transaction
        $updated = Model::getBy(['id' => $model->id]);

        // Make assertions
        $this->assertEquals('Updated', $updated->name);

        // Everything rolls back automatically
    }
}
```

**Only override if you have a specific need:**
```php
class SpecialTest extends Test
{
    protected bool $useTransactions = false; // Only if absolutely necessary

    // Then you must handle cleanup manually
}
```
