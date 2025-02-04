<?php declare(strict_types=1);
namespace Tests\Feature\Proto\Utils\Files\Disks\Drivers;

use Tests\Test;
use Proto\Http\UploadFile;
use Proto\Utils\Files\Disks\Drivers\S3Driver;

/**
 * S3DriverTest
 */
final class S3DriverTest extends Test
{
    /**
     * This holds the bucket.
     *
     * @var string $bucket
     */
    protected const BUCKET = 'essentialstestfiles';

    /**
     * This will be called when the test is set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // do something on setup
    }

    /**
     * This creates a test file.
     *
     * @return UploadFile
     */
    protected function getTestFile(): UploadFile
    {
        $fileName = 's3-test-file.txt';

        /**
         * This wil get the system tmp dir.
         */
        $tmpDir = sys_get_temp_dir();
        $path = "{$tmpDir}/{$fileName}";

        /**
         * This will move the file into the tmp folder.
         */
        $content = 'This is a test file.';
        file_put_contents($path, $content);

        /**
         * We need to create an update object to use with the
         * s3 driver store method.
         */
        return new UploadFile([
            'name' => $fileName,
            'full_path' => $fileName,
            'tmp_name' => $path,
            'type' => 'text/plain',
            'error' => 0,
            'size' => 21
        ]);
    }

    /**
     * This tests the store method.
     *
     * @return void
     */
    public function testStore(): void
    {
        $uploadFile = $this->getTestFile();
        $result = $uploadFile->store('s3', self::BUCKET);
        $this->assertTrue($result);

        /**
         * Delete the file from s3.
         */
        $fileName = $uploadFile->getNewName();
        $this->delete($fileName);
    }

    /**
     * This tests the add method.
     *
     * @return void
     */
    public function testAdd(): void
    {
        /**
         * Create test file.
         */
        $file = $this->getTestFile();
        $fileName = $file->getFilePath();

        /**
         * Add the file to s3.
         */
        $driver = new S3Driver(self::BUCKET);
        $result = $driver->add($fileName);
        $this->assertTrue($result);

        /**
         * Delete the file from s3.
         */
        $fileName = $file->getNewName();
        $this->delete($fileName);
    }

    /**
     * This tests the get method.
     *
     * @return void
     */
    public function testGet(): void
    {
        /**
         * Create a test file and upload to s3.
         */
        $file = $this->getTestFile();
        $file->store('s3', self::BUCKET);

        /**
         * Use the fileName to get the file from s3.
         */
        $fileName = $file->getName();
        $driver = new S3Driver(self::BUCKET);
        $result = $driver->get($fileName);
        $this->assertIsString($result);

        /**
         * Delete the file from s3.
         */
        $this->delete($fileName);
    }

    /**
     * This tests the delete method.
     *
     * @return void
     */
    public function testDelete(): void
    {
        /**
         * Create a test file and upload to s3.
         */
        $file = $this->getTestFile();
        $file->store('s3', self::BUCKET);

        /**
         * Use the fileName to delete the file from s3.
         */
        $fileName = $file->getName();
        $result = $this->delete($fileName);
        $this->assertTrue($result);
    }

    /**
     * This deletes a file from s3.
     *
     * @param string $fileName
     * @return boolean
     */
    protected function delete(string $fileName): bool
    {
        $driver = new S3Driver(self::BUCKET);
        return $driver->delete($fileName);
    }

    /**
     * This will be called when the test is torn down.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // do something on tear down
    }
}