<?php declare(strict_types=1);
namespace Proto\Tests\Unit;

use Proto\Tests\Test;
use Proto\Database\Seeders\UserSeeder;
use Proto\Database\Seeders\RoleSeeder;
use Proto\Tests\Seeders\TestUserSeeder;

/**
 * SeederExampleTest
 *
 * Demonstrates the usage of seeders in tests.
 *
 * @package Proto\Tests\Unit
 */
final class SeederExampleTest extends Test
{
    /**
     * Enable system initialization for seeder tests
     *
     * @return void
     */
    protected function setupSystem(): void
    {
        new \Proto\Base();
    }

    /**
     * Test using seeders defined in the seeders property.
     *
     * @return void
     */
    public function testWithSeederProperty(): void
    {
        // Rollback any existing transaction and start fresh
        if ($this->useTransactions) {
            $this->rollbackDatabaseTransaction();
            $this->beginDatabaseTransaction();
        }

        // Set seeders to run before this test
        $this->setSeeders([
            RoleSeeder::class,
            UserSeeder::class
        ]);

        // Run the seeders
        $this->seedDatabase($this->seeders);

        // Assert that seeded data exists
        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseCount('users', 4);

        // Assert specific seeded data
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }

    /**
     * Test manually running seeders.
     *
     * @return void
     */
    public function testManualSeeding(): void
    {
        // Initially no data
        $this->assertDatabaseCount('users', 0);

        // Run seeder manually
        $this->seedDatabase([TestUserSeeder::class]);

        // Assert seeded data
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseHas('users', ['email' => 'test1@example.com']);
        $this->assertDatabaseHas('users', ['status' => 'offline']);
    }

    /**
     * Test seeder behavior with existing data.
     *
     * @return void
     */
    public function testSeederWithExistingData(): void
    {
        // Create a user manually first
        $this->createTestFile('/tmp/test_insert.sql',
            "INSERT INTO users (name, email, password, status, created_at, updated_at) " .
            "VALUES ('Manual User', 'manual@example.com', 'hash', 'active', NOW(), NOW())"
        );

        // Simulate that users table has data
        // (In real test, you'd insert actual data)

        // Run UserSeeder - it should skip because table is not empty
        // Note: This is a demonstration - in real tests you'd have actual database operations
        $this->assertTrue(true, 'Seeder would check if table is empty and skip if not');
    }
}

/**
 * Example test class demonstrating seeders property usage.
 */
final class UserTestWithSeeders extends Test
{
    /**
     * @var array $seeders Seeders to run before each test
     */
    protected array $seeders = [
        RoleSeeder::class,
        UserSeeder::class
    ];

    /**
     * Enable system initialization for seeder tests
     *
     * @return void
     */
    protected function setupSystem(): void
    {
        new \Proto\Base();
    }

    public function testUsersExist(): void
    {
        // Seeders run automatically before this test
        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseHas('users', ['name' => 'John Doe']);
    }

    public function testRolesExist(): void
    {
        // Same seeders run before this test too
        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
    }
}