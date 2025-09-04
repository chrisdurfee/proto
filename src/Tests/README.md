# Proto Framework Testing System

The Proto framework now includes a comprehensive testing system built on top of PHPUnit, providing powerful utilities to make testing more efficient and maintainable.

## Overview

The enhanced testing system provides:
- Database testing utilities with automatic transaction handling
- Model creation and assertion helpers
- HTTP request testing with fluent assertions
- Mock and spy helpers for service testing
- File system testing utilities
- Test data management and fake data generation
- Comprehensive assertion helpers

## Getting Started

All test classes should extend the `Proto\Tests\Test` base class:

```php
<?php declare(strict_types=1);
namespace Modules\YourModule\Tests\Unit;

use Proto\Tests\Test;

class YourTest extends Test
{
    public function testSomething(): void
    {
        // Your test code here
    }
}
```

## Database Testing

### Database Transactions
By default, each test runs in a database transaction that is rolled back after the test completes:

```php
class UserTest extends Test
{
    public function testUserCreation(): void
    {
        // This will be rolled back automatically
        $this->assertDatabaseCount('users', 0);
    }

    // Disable transactions for specific tests
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUseTransactions(false);
    }
}
```

### Database Assertions
```php
// Assert table contains data
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);

// Assert table doesn't contain data
$this->assertDatabaseMissing('users', ['email' => 'deleted@example.com']);

// Assert table count
$this->assertDatabaseCount('users', 5);
```

### Database Seeding
```php
class UserTest extends Test
{
    protected array $seeders = [
        UserSeeder::class,
        RoleSeeder::class
    ];

    public function testWithSeededData(): void
    {
        // Seeders run automatically before each test
        $this->assertDatabaseCount('users', 10);
    }
}
```

## Model Testing

### Model Creation
```php
// Create and persist a model
$user = $this->createModel(User::class, [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Create model without persisting
$user = $this->makeModel(User::class, ['name' => 'John Doe']);

// Create multiple models
$users = $this->createMultiple(User::class, 5, ['status' => 'active']);
```

### Model Assertions
```php
// Assert model exists in database
$this->assertModelExists($user);

// Assert model doesn't exist
$this->assertModelMissing($deletedUser);

// Assert models are equal
$this->assertModelEquals($expected, $actual);

// Assert model has specific attributes
$this->assertModelHasAttributes($user, [
    'name' => 'John Doe',
    'status' => 'active'
]);
```

## HTTP Testing

### Making Requests
```php
// JSON requests
$response = $this->getJson('/api/users');
$response = $this->postJson('/api/users', ['name' => 'John']);
$response = $this->putJson('/api/users/1', ['name' => 'Jane']);
$response = $this->patchJson('/api/users/1', ['status' => 'inactive']);
$response = $this->deleteJson('/api/users/1');

// Regular requests
$response = $this->get('/users');
$response = $this->post('/users', ['name' => 'John']);
```

### Authentication
```php
// Test as authenticated user
$user = $this->createModel(User::class);
$response = $this->actingAs($user)->getJson('/api/profile');

// Test with token
$response = $this->withToken($jwt)->getJson('/api/protected');

// Test with session data
$response = $this->withSession(['user_id' => 1])->get('/dashboard');
```

### Response Assertions
```php
$response = $this->getJson('/api/users');

// Status assertions
$response->assertStatus(200);
$response->assertSuccessful();
$response->assertRedirect('/login');

// JSON assertions
$response->assertJson(['status' => 'success']);
$response->assertJsonFragment(['name' => 'John Doe']);
$response->assertJsonMissing(['password']);
$response->assertJsonStructure([
    'data' => ['*' => ['id', 'name', 'email']],
    'meta' => ['total', 'per_page']
]);
```

## Mock and Spy Helpers

### Creating Mocks
```php
// Mock a service
$mockService = $this->mockService(EmailService::class);
$this->expectMethodCall($mockService, 'send', ['test@example.com'], true);

// Create a spy
$spyService = $this->spyService(LogService::class);

// Partial mock
$partialMock = $this->partialMock(PaymentService::class, ['charge']);

// Stub with predefined returns
$stub = $this->createStub(ConfigService::class, [
    'get' => 'test_value',
    'has' => true
]);
```

### Mock Expectations
```php
$mock = $this->mockService(NotificationService::class);

// Expect method to be called with specific arguments
$this->expectMethodCall($mock, 'notify', ['user@example.com', 'Welcome!']);

// Expect method never to be called
$this->expectMethodNeverCalled($mock, 'sendSms');

// Fluent expectations
$mock = $this->mockWithExpectations(UserService::class, function($mock, $test) {
    $mock->expects($test->once())
         ->method('create')
         ->with(['name' => 'John'])
         ->willReturn(new User());
});
```

## File System Testing

### File Operations
```php
// Create test file
$this->createTestFile('/tmp/test.txt', 'Hello World');

// Create test directory
$this->createTestDirectory('/tmp/test_dir');

// Copy file for testing
$this->copyFileForTest('/path/to/source', '/tmp/copy.txt');
```

### File Assertions
```php
// Assert file exists/doesn't exist
$this->assertTestFileExists('/path/to/file');
$this->assertTestFileNotExists('/path/to/deleted');

// Assert file contents
$this->assertFileContains('/path/to/file', 'expected content');
$this->assertFileNotContains('/path/to/file', 'unwanted content');

// Assert directory state
$this->assertTestDirectoryExists('/path/to/dir');
$this->assertDirectoryEmpty('/path/to/empty_dir');
$this->assertDirectoryContainsFile('/path/to/dir', 'file.txt');

// Get file properties
$content = $this->getFileContent('/path/to/file');
$size = $this->getFileSize('/path/to/file');
```

## Test Data and Fixtures

### Test Data Management
```php
// Set test data
$this->setTestData('api_key', 'test-key-123');
$this->withTestData([
    'environment' => 'testing',
    'debug' => true
]);

// Get test data
$apiKey = $this->getTestData('api_key');
$debug = $this->getTestData('debug', false); // with default
```

### Fake Data Generation
```php
$faker = $this->fake();

// Generate fake data
$name = $faker->name();
$email = $faker->email();
$phone = $faker->phoneNumber();
$address = $faker->address();
$text = $faker->text(50); // 50 words
$number = $faker->numberBetween(1, 100);
$date = $faker->dateTimeBetween('-1 year', 'now');
$uuid = $faker->uuid();
```

### Loading Fixtures
```php
// Load fixture data (supports JSON, PHP, YAML)
$userData = $this->loadFixture('users.json');
$config = $this->loadFixture('config.php');

// Create temporary files
$tempFile = $this->createTempFile('test content', 'txt');
$tempDir = $this->createTempDirectory();
```

## Enhanced Assertions

### Collection Assertions
```php
$collection = [1, 2, 3, 4, 5];
$this->assertCollectionContains(3, $collection);
$this->assertCollectionCount(5, $collection);
$this->assertCollectionEmpty([]);
$this->assertCollectionNotEmpty($collection);
```

### Array Assertions
```php
$array = ['name' => 'John', 'age' => 30, 'city' => 'NYC'];
$this->assertArrayHasKeys(['name', 'age'], $array);
$this->assertArrayMissingKeys(['password', 'secret'], $array);
```

### String Assertions
```php
$this->assertStringMatchesPattern('/^\d{3}-\d{3}-\d{4}$/', $phoneNumber);
$this->assertStringContainsAll(['hello', 'world'], 'hello beautiful world');
```

### Validation Assertions
```php
$this->assertValidEmail('test@example.com');
$this->assertValidUrl('https://example.com');
$this->assertBetween(10, 20, 15);
$this->assertRecentTimestamp(time());
$this->assertRecentDate('2025-09-04 10:30:00');
```

## Configuration

### Test Environment Setup
```php
class MyTest extends Test
{
    // Disable database transactions
    protected bool $useTransactions = false;

    // Set seeders to run
    protected array $seeders = [
        UserSeeder::class,
        ProductSeeder::class
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Custom setup
        $this->withTestData(['custom_config' => true]);
    }

    protected function tearDown(): void
    {
        // Custom cleanup
        parent::tearDown();
    }
}
```

### PHPUnit Configuration
The testing system integrates with your existing `phpunit.xml`:

```xml
<phpunit>
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./src/Tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./src/Tests/Feature</directory>
        </testsuite>
    </testsuites>

    <php>
        <server name="APP_ENV" value="testing"/>
        <server name="DB_CONNECTION" value="testing"/>
    </php>
</phpunit>
```

## Best Practices

1. **Use descriptive test names** that explain what is being tested
2. **Arrange, Act, Assert** - structure your tests clearly
3. **Use database transactions** to keep tests isolated
4. **Mock external dependencies** to keep tests fast and reliable
5. **Clean up resources** - the framework handles this automatically
6. **Use faker for test data** instead of hardcoded values
7. **Test both happy and error paths**
8. **Keep tests focused** - one assertion per test method when possible

## Example Test Structure

```php
<?php declare(strict_types=1);
namespace Modules\User\Tests\Unit;

use Proto\Tests\Test;
use Modules\User\Models\User;
use Modules\User\Services\UserService;

class UserServiceTest extends Test
{
    protected array $seeders = [RoleSeeder::class];

    public function testCreatesUserWithValidData(): void
    {
        // Arrange
        $userData = [
            'name' => $this->fake()->name(),
            'email' => $this->fake()->email(),
            'role_id' => 1
        ];

        $service = new UserService();

        // Act
        $user = $service->create($userData);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertModelExists($user);
        $this->assertDatabaseHas('users', ['email' => $userData['email']]);
        $this->assertEquals($userData['name'], $user->name);
    }

    public function testThrowsExceptionWithInvalidEmail(): void
    {
        // Arrange
        $userData = ['email' => 'invalid-email'];
        $service = new UserService();

        // Act & Assert
        $this->expectException(ValidationException::class);
        $service->create($userData);
    }
}
```

This enhanced testing system transforms your Proto framework testing experience from writing repetitive boilerplate to focusing on actual test logic, making your test suite more maintainable and encouraging better test coverage across your modular monolith applications.