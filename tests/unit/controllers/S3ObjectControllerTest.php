<?php declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Proto\Utils\Files\Disks\Drivers\Helpers\Aws\S3\Controllers\S3ObjectController;
use Proto\Utils\Files\Disks\Drivers\Helpers\Aws\S3\Models\S3Object;

/**
 * Class S3ObjectControllerTest
 *
 * This class is responsible for testing the "S3ObjectController" class methods.
 *
 * @package Tests\Unit
 */
class S3ObjectControllerTest extends TestCase
{
    /**
     * @var S3ObjectController $s3ObjectController
     */
    private S3ObjectController $s3ObjectController;

    /**
     * Setup method for setting initial requirements for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Here S3Object is just a stub.
        // In actual tests, you may need to include additional setup like mocking.
        $this->s3ObjectController = new S3ObjectController(S3Object::class);
    }

    /**
     * Test for the constructor method.
     *
     * @return void
     */
    public function testConstructor(): void
    {
        $this->assertInstanceOf(
            S3ObjectController::class,
            new S3ObjectController(S3Object::class)
        );
    }

    /**
     * Test for the getByName method.
     *
     * @return void
     */
    public function testGetByName(): void
    {
        // Verify here if the returned object is instance of the expected class
        // You should replace 'ExpectedClassName' with the actual expected class name
        $this->assertIsObject(
            $this->s3ObjectController->getByName('test-file-name')
        );

        $this->assertIsNotObject(
            $this->s3ObjectController->getByName('test-file-name')
        );
    }
}