<?php declare(strict_types=1);
namespace Tests;

use Proto\Utils\Files\Disks\Drivers\Helpers\Aws\S3\S3Integration;

/**
 * This holds the bucket name.
 *
 * @var string BUCKET
 */
const BUCKET_NAME = 'essentialstestfiles';

/**
 * Class S3IntegrationTest
 *
 * This will test the S3Integration class.
 *
 * @package Tests
 */
class S3IntegrationTest extends Test
{
    /**
     * This holds the S3Integration instance.
     *
     * @var S3Integration
     */
    protected S3Integration $s3Integration;

    /**
     * This holds the test file name.
     *
     * @var string
     */
    protected string $testFileName;

    /**
     * This holds the remote path.
     *
     * @var string
     */
    protected string $remotePath;

    /**
     * Sets up instances and mock objects for the S3Integration class methods.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $s3 = env('files')->amazon->s3 ?? null;
        $credentials = $s3->credentials ?? null;
        $config = $s3->bucket->{BUCKET_NAME} ?? null;

        $this->s3Integration = new S3Integration($credentials, $config);
        $this->testFileName = $this->getRandomFileName();
        $this->remotePath = $config->path ?? null;
    }

    /**
     * This will create a random file name.
     *
     * @return string
     */
    protected function getRandomFileName(): string
    {
        $random = rand(0, 1000);
        return "test{$random}.txt";
    }

    /**
     * This will get the tmp path.
     *
     * @param string $fileName
     * @return string
     */
    protected function getTmpFilePath(string $fileName): string
    {
        $tmpDir = sys_get_temp_dir();
        return "{$tmpDir}/{$fileName}";
    }

    /**
     * This will create a test file and
     * store it in the tmp directory.
     *
     * @return void
     */
    protected function createTestFile(): void
    {
        /**
         * This wil get the system tmp dir.
         */
        $path = $this->getTmpFilePath($this->testFileName);

        /**
         * This will move the file into the tmp folder.
         */
        $content = 'This is a test file.';
        file_put_contents($path, $content);
    }

    /**
     * Test function upload
     *
     * @return void
     */
    public function testUpload(): void
    {
        $this->createTestFile();

        $result = $this->s3Integration->upload(
            $this->getTmpFilePath($this->testFileName),
            $this->remotePath,
            BUCKET_NAME
        );
        $this->assertTrue($result);

        /**
         * Clean up.
         */
        $this->s3Integration->delete(BUCKET_NAME, $this->remotePath . $this->testFileName);
    }

    /**
     * Test that a file URL can be successfully retrieved.
     *
     * @return void
     */
    public function testGetFileUrl(): void
    {
        $result = $this->s3Integration->getFileUrl('test.txt');
        $this->assertIsString($result);
    }

    /**
     * Test that an object can be successfully deleted.
     *
     * @return void
     */
    public function testDelete(): void
    {
        /**
         * Create test file and upload.
         */
        $this->createTestFile();
        $tmpPath = $this->getTmpFilePath($this->testFileName);
        $this->s3Integration->upload(
            $tmpPath,
            $this->remotePath,
            BUCKET_NAME
        );

        $result = $this->s3Integration->delete(BUCKET_NAME, $tmpPath);
        $this->assertTrue($result);
    }

    /**
     * Test that an object's URL can be successfully retrieved.
     *
     * @return void
     */
    public function testGetObjectUrl(): void
    {
        $result = $this->s3Integration->getObjectUrl(BUCKET_NAME, 'test.txt');
        $this->assertIsString($result);
    }
}