<?php declare(strict_types=1);
namespace Proto\Tests\Unit;

use Proto\Tests\Test;
use Proto\Tests\Examples\User;
use Proto\Tests\Examples\UserFactory;

/**
 * FactoryExampleTest
 *
 * Demonstrates the usage of the Proto factory system.
 * Shows all available patterns and features.
 *
 * @package Proto\Tests\Unit
 */
final class FactoryExampleTest extends Test
{
	/**
	 * Don't use database transactions for this example
	 * (factories work with or without transactions)
	 *
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * Test basic factory usage - creating a single model.
	 *
	 * @return void
	 */
	public function testBasicFactoryCreate(): void
	{
		// Create a single user using factory
		$user = User::factory()->create();

		$this->assertInstanceOf(User::class, $user);
		$this->assertNotNull($user->id);
		$this->assertNotEmpty($user->name);
		$this->assertNotEmpty($user->email);
		$this->assertEquals('active', $user->status);
		$this->assertEquals('user', $user->role);
	}

	/**
	 * Test making models without persisting to database.
	 *
	 * @return void
	 */
	public function testFactoryMake(): void
	{
		// Make a user without saving to database
		$user = User::factory()->make();

		$this->assertInstanceOf(User::class, $user);
		$this->assertNull($user->id); // Not saved, so no ID
		$this->assertNotEmpty($user->name);
		$this->assertNotEmpty($user->email);
	}

	/**
	 * Test creating multiple models at once.
	 *
	 * @return void
	 */
	public function testFactoryMultiple(): void
	{
		// Create 5 users
		$users = User::factory()->count(5)->create();

		$this->assertCount(5, $users);
		$this->assertInstanceOf(User::class, $users[0]);
		$this->assertNotNull($users[0]->id);

		// Alternative syntax
		$users2 = User::factory(5)->create();
		$this->assertCount(5, $users2);
	}

	/**
	 * Test overriding factory attributes.
	 *
	 * @return void
	 */
	public function testFactoryWithAttributes(): void
	{
		// Create user with specific attributes
		$user = User::factory()->create([
			'name' => 'John Doe',
			'email' => 'john@example.com'
		]);

		$this->assertEquals('John Doe', $user->name);
		$this->assertEquals('john@example.com', $user->email);
		$this->assertEquals('active', $user->status); // Still uses factory default
	}

	/**
	 * Test using factory states.
	 *
	 * @return void
	 */
	public function testFactoryStates(): void
	{
		// Create an admin user
		$admin = User::factory()->state('admin')->create();

		$this->assertEquals('admin', $admin->role);
		$this->assertEquals('active', $admin->status);

		// Create an inactive user
		$inactiveUser = User::factory()->state('inactive')->create();

		$this->assertEquals('inactive', $inactiveUser->status);

		// Create a suspended user
		$suspendedUser = User::factory()->state('suspended')->create();

		$this->assertEquals('suspended', $suspendedUser->status);
	}

	/**
	 * Test using states with parameters.
	 *
	 * @return void
	 */
	public function testFactoryStatesWithParameters(): void
	{
		// Create user with custom email domain
		$user = User::factory()
			->state('withDomain', 'company.com')
			->create();

		$this->assertStringEndsWith('@company.com', $user->email);
	}

	/**
	 * Test chaining multiple states.
	 *
	 * @return void
	 */
	public function testFactoryMultipleStates(): void
	{
		// Create a verified admin user
		$admin = User::factory()
			->state('admin')
			->state('verified')
			->create();

		$this->assertEquals('admin', $admin->role);
		$this->assertNotNull($admin->email_verified_at ?? null);
	}

	/**
	 * Test using callable states.
	 *
	 * @return void
	 */
	public function testFactoryCallableState(): void
	{
		// Use a callable to modify state
		$user = User::factory()
			->state(function ($attributes) {
				return [
					'name' => strtoupper($attributes['name']),
					'status' => 'premium'
				];
			})
			->make();

		$this->assertEquals(strtoupper($user->name), $user->name);
		$this->assertEquals('premium', $user->status);
	}

	/**
	 * Test creating raw attribute arrays.
	 *
	 * @return void
	 */
	public function testFactoryRaw(): void
	{
		// Get raw attributes without creating model
		$attributes = User::factory()->raw();

		$this->assertIsArray($attributes);
		$this->assertArrayHasKey('name', $attributes);
		$this->assertArrayHasKey('email', $attributes);
		$this->assertArrayHasKey('password', $attributes);

		// Create multiple raw attribute sets
		$multipleAttributes = User::factory()->count(3)->raw();

		$this->assertCount(3, $multipleAttributes);
		$this->assertIsArray($multipleAttributes[0]);
	}

	/**
	 * Test using set() method to override attributes.
	 *
	 * @return void
	 */
	public function testFactorySet(): void
	{
		$user = User::factory()
			->set([
				'name' => 'Jane Smith',
				'role' => 'moderator'
			])
			->create();

		$this->assertEquals('Jane Smith', $user->name);
		$this->assertEquals('moderator', $user->role);
	}

	/**
	 * Test creating models in sequence.
	 *
	 * @return void
	 */
	public function testFactorySequence(): void
	{
		// Create users with sequential attributes
		$users = User::factory()
			->count(3)
			->sequence(function ($index) {
				return [
					'name' => "User {$index}",
					'email' => "user{$index}@example.com"
				];
			});

		$this->assertCount(3, $users);
		$this->assertEquals('User 1', $users[0]->name);
		$this->assertEquals('User 2', $users[1]->name);
		$this->assertEquals('User 3', $users[2]->name);
	}

	/**
	 * Test afterMaking and afterCreating callbacks.
	 *
	 * @return void
	 */
	public function testFactoryCallbacks(): void
	{
		$callbackExecuted = false;

		$user = User::factory()
			->afterMaking(function ($user) use (&$callbackExecuted) {
				// This runs after making but before saving
				$user->set('name', 'Modified Name');
			})
			->afterCreating(function ($user) use (&$callbackExecuted) {
				// This runs after saving to database
				$callbackExecuted = true;
			})
			->create();

		$this->assertEquals('Modified Name', $user->name);
		$this->assertTrue($callbackExecuted);
	}

	/**
	 * Test using the factory helper in tests.
	 *
	 * @return void
	 */
	public function testFactoryHelper(): void
	{
		// Using the factory() helper from Test class
		$user = $this->factory(User::class)->create();

		$this->assertInstanceOf(User::class, $user);
		$this->assertNotNull($user->id);
	}

	/**
	 * Test factory with static times() method.
	 *
	 * @return void
	 */
	public function testFactoryTimes(): void
	{
		// Alternative syntax for creating multiple
		$users = UserFactory::times(5)->create();

		$this->assertCount(5, $users);
	}

	/**
	 * Test factory with static new() method.
	 *
	 * @return void
	 */
	public function testFactoryNew(): void
	{
		// Create factory with new() method
		$user = UserFactory::new()
			->set(['name' => 'Test User'])
			->create();

		$this->assertEquals('Test User', $user->name);
	}

	/**
	 * Demonstrates real-world usage pattern.
	 *
	 * @return void
	 */
	public function testRealWorldUsage(): void
	{
		// Scenario: Testing a user management system

		// Create regular users
		$users = User::factory()->count(10)->create();

		// Create some admin users
		$admins = User::factory()
			->count(3)
			->state('admin')
			->create();

		// Create a specific test user
		$testUser = User::factory()->create([
			'email' => 'test@example.com',
			'name' => 'Test User'
		]);

		// Create inactive users for testing activation flow
		$inactiveUsers = User::factory()
			->count(5)
			->state('inactive')
			->create();

		$this->assertCount(10, $users);
		$this->assertCount(3, $admins);
		$this->assertEquals('test@example.com', $testUser->email);
		$this->assertCount(5, $inactiveUsers);

		// Verify admin users have correct role
		foreach ($admins as $admin) {
			$this->assertEquals('admin', $admin->role);
		}

		// Verify inactive users have correct status
		foreach ($inactiveUsers as $user) {
			$this->assertEquals('inactive', $user->status);
		}
	}
}
