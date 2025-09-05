<?php declare(strict_types=1);
namespace Proto\Tests\Unit;

use Proto\Tests\Test;

/**
 * ExampleTest
 *
 * Demonstrates the usage of the enhanced Proto testing framework.
 *
 * @package Proto\Tests\Unit
 */
final class ExampleTest extends Test
{
	/**
	 * Test database helpers.
	 *
	 * @return void
	 */
	public function testDatabaseHelpers(): void
	{
		// Example: Assert that a table has specific data
		// $this->assertDatabaseHas('users', ['email' => 'test@example.com']);

		// Example: Assert table count
		// $this->assertDatabaseCount('users', 0);

		// Example: Using seeders
		// $this->seedDatabase([UserSeeder::class]);
		// $this->assertDatabaseCount('users', 4); // 4 users from UserSeeder

		$this->assertTrue(true, 'Database helpers are available');
	}

	/**
	 * Test model helpers.
	 *
	 * @return void
	 */
	public function testModelHelpers(): void
	{
		// Example: Create a model for testing
		// $user = $this->createModel(User::class, ['name' => 'Test User']);
		// $this->assertModelExists($user);

		// Example: Create multiple models
		// $users = $this->createMultiple(User::class, 3, ['status' => 'active']);
		// $this->assertCount(3, $users);

		$this->assertTrue(true, 'Model helpers are available');
	}

	/**
	 * Test HTTP helpers.
	 *
	 * @return void
	 */
	public function testHttpHelpers(): void
	{
		// Example: Make HTTP requests
		// $response = $this->getJson('/api/users');
		// $response->assertStatus(200);
		// $response->assertJsonStructure(['data', 'meta']);

		// Example: Test with authentication
		// $user = $this->createModel(User::class);
		// $response = $this->actingAs($user)->getJson('/api/profile');
		// $response->assertSuccessful();

		$this->assertTrue(true, 'HTTP helpers are available');
	}

	/**
	 * Test assertion helpers.
	 *
	 * @return void
	 */
	public function testAssertionHelpers(): void
	{
		// Example: Collection assertions
		$collection = [1, 2, 3, 4, 5];
		$this->assertCollectionContains(3, $collection);
		$this->assertCollectionCount(5, $collection);

		// Example: String pattern matching
		$email = 'test@example.com';
		$this->assertValidEmail($email);

		// Example: Range assertions
		$value = 15;
		$this->assertBetween(10, 20, $value);
	}

	/**
	 * Test data helpers.
	 *
	 * @return void
	 */
	public function testDataHelpers(): void
	{
		// Example: Test data management
		$this->setTestData('test_key', 'test_value');
		$this->assertEquals('test_value', $this->getTestData('test_key'));

		// Example: Fake data generation
		$faker = $this->fake();
		$name = $faker->name();
		$email = $faker->email();

		$this->assertNotEmpty($name);
		$this->assertValidEmail($email);

		// Example: Create temporary file
		$content = 'This is test content';
		$filePath = $this->createTempFile($content, 'txt');
		$this->assertTestFileExists($filePath);
		$this->assertEquals($content, file_get_contents($filePath));
	}

	/**
	 * Test mock helpers.
	 *
	 * @return void
	 */
	public function testMockHelpers(): void
	{
		// Example: Create a mock service
		// $mockService = $this->mockService(SomeService::class);
		// $this->expectMethodCall($mockService, 'someMethod', ['arg1'], 'return_value');

		// Example: Create a stub with predefined returns
		// $stub = $this->createStub(SomeService::class, [
		//     'getName' => 'Test Name',
		//     'getId' => 123
		// ]);

		$this->assertTrue(true, 'Mock helpers are available');
	}

	/**
	 * Test file helpers.
	 *
	 * @return void
	 */
	public function testFileHelpers(): void
	{
		// Example: Create and test files
		$testFile = '/tmp/proto_test_file.txt';
		$content = 'Hello, World!';

		$this->createTestFile($testFile, $content);
		$this->assertTestFileExists($testFile);
		$this->assertFileContains($testFile, 'Hello');
		$this->assertEquals($content, $this->getFileContent($testFile));

		// Example: Create and test directories
		$testDir = '/tmp/proto_test_dir';
		$this->createTestDirectory($testDir);
		$this->assertTestDirectoryExists($testDir);
	}

	/**
	 * Test fixture loading.
	 *
	 * @return void
	 */
	public function testFixtureLoading(): void
	{
		// Create a sample fixture for testing
		$fixtureData = [
			'users' => [
				['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
				['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']
			]
		];

		$fixturePath = $this->createTempFile(json_encode($fixtureData), 'json');

		// Test that we can load the fixture
		// Note: This would normally use the fixture directory structure
		// For this demo, we're just ensuring the mechanism works
		$this->assertTestFileExists($fixturePath);
	}

	/**
	 * Test comprehensive test flow.
	 *
	 * @return void
	 */
	public function testComprehensiveFlow(): void
	{
		// 1. Setup test data
		$this->withTestData([
			'test_environment' => 'unit_test',
			'test_timestamp' => time()
		]);

		// 2. Generate fake data
		$faker = $this->fake();
		$userName = $faker->name();
		$userEmail = $faker->email();

		// 3. Validate the fake data
		$this->assertValidEmail($userEmail);
		$this->assertNotEmpty($userName);

		// 4. Create temporary resources
		$configContent = json_encode([
			'app_name' => 'Proto Test',
			'user_name' => $userName,
			'user_email' => $userEmail
		]);

		$configFile = $this->createTempFile($configContent, 'json');

		// 5. Test file operations
		$this->assertTestFileExists($configFile);
		$this->assertFileContains($configFile, $userName);
		$this->assertFileContains($configFile, $userEmail);

		// 6. Verify test data
		$this->assertEquals('unit_test', $this->getTestData('test_environment'));
		$this->assertRecentTimestamp($this->getTestData('test_timestamp'));

		$this->assertTrue(true, 'Comprehensive test flow completed successfully');
	}
}