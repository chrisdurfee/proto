<?php declare(strict_types=1);
namespace Proto\Utils\Files\Disks\Drivers;

include_once __DIR__ . '/../../../../../vendor/autoload.php';

use Proto\Utils\Files\Disks\Drivers\Helpers\Aws\S3\S3Integration;
use Proto\Utils\Files\File;
use Proto\Http\UploadFile;

/**
 * S3Driver
 *
 * Handles file operations on Amazon S3.
 *
 * @package Proto\Utils\Files\Disks\Drivers
 */
class S3Driver extends Driver
{
	/**
	 * @var string $bucket The name of the S3 bucket.
	 */
	protected string $bucket;

	/**
	 * @var string $path The path within the bucket.
	 */
	protected string $path;

	/**
	 * @var S3Integration $s3 The S3 client instance.
	 */
	protected S3Integration $s3;

	/**
	 * Initializes the S3 driver.
	 *
	 * @param string $bucket The S3 bucket name.
	 * @throws \Exception If the bucket or S3 configuration is invalid.
	 */
	public function __construct(string $bucket)
	{
		parent::__construct($bucket);

		if (empty($bucket)) {
			throw new \Exception("No bucket name provided.");
		}

		$this->initializeS3($bucket);
	}

	/**
	 * Initializes S3 settings and credentials.
	 *
	 * @param string $bucketName The S3 bucket name.
	 * @return void
	 * @throws \Exception If configuration or credentials are missing.
	 */
	protected function initializeS3(string $bucketName): void
	{
		$s3Config = env('files')->amazon->s3 ?? null;
		if ($s3Config === null)
		{
			throw new \Exception("No S3 configurations found.");
		}

		$bucketConfig = $s3Config->bucket->{$bucketName} ?? null;
		if ($bucketConfig === null)
		{
			throw new \Exception("No S3 configurations found for bucket: {$bucketName}.");
		}

		$credentials = $s3Config->credentials ?? null;
		if ($credentials === null)
		{
			throw new \Exception("No S3 credentials found.");
		}

		$this->bucket = $bucketConfig->name ?? throw new \Exception("Bucket name not set in configuration.");
		$this->path = rtrim($bucketConfig->path ?? '', '/') . '/';
		$this->s3 = new S3Integration($credentials, $bucketConfig);
	}

	/**
	 * Stores an uploaded file on S3.
	 *
	 * @param UploadFile $uploadFile The uploaded file.
	 * @return bool Success status.
	 */
	public function store(UploadFile $uploadFile): bool
	{
		return $this->add($uploadFile->getFilePath());
	}

	/**
	 * Uploads a file to S3.
	 *
	 * @param string $fileName The local file name.
	 * @return bool Success status.
	 */
	public function add(string $fileName): bool
	{
		return $this->s3->upload($fileName, $this->path, $this->bucket);
	}

	/**
	 * Retrieves a file's public URL from S3.
	 *
	 * @param string $fileName The file name.
	 * @return string The file URL.
	 */
	public function get(string $fileName): string
	{
		return $this->s3->getFileUrl($fileName) ?? '';
	}

	/**
	 * Downloads a file from S3.
	 *
	 * @param string $fileName The file name.
	 * @return void
	 */
	public function download(string $fileName): void
	{
		File::stream($this->getStoredPath($fileName));
	}

	/**
	 * Deletes a file from S3.
	 *
	 * @param string $fileName The file name.
	 * @return bool Success status.
	 */
	public function delete(string $fileName): bool
	{
		$key = $this->path . $fileName;
		return $this->s3->delete($this->bucket, $key);
	}

	/**
	 * Retrieves the stored file path in S3.
	 *
	 * @param string $fileName The file name.
	 * @return string The object URL.
	 */
	public function getStoredPath(string $fileName): string
	{
		$key = $this->path . $fileName;
		return $this->s3->getObjectUrl($this->bucket, $key);
	}

	/**
	 * Renaming files is not supported in S3 natively.
	 *
	 * @param string $oldFileName The old file name.
	 * @param string $newFileName The new file name.
	 * @return bool Always returns false.
	 */
	public function rename(string $oldFileName, string $newFileName): bool
	{
		return false;
	}

	/**
	 * Moving files is not supported in S3 natively.
	 *
	 * @param string $oldFileName The old file name.
	 * @param string $newFileName The new file name.
	 * @return bool Always returns false.
	 */
	public function move(string $oldFileName, string $newFileName): bool
	{
		return false;
	}

	/**
	 * Retrieves the size of a file.
	 *
	 * @param string $fileName The file name.
	 * @return int Always returns 0 (not supported by S3 API).
	 */
	public function getSize(string $fileName): int
	{
		return 0;
	}

	/**
	 * Lists objects in the S3 bucket.
	 *
	 * @param string|null $continuationToken Continuation token for paginated results.
	 * @return object The list of objects.
	 */
	public function listObjects(?string $continuationToken = null): object
	{
		return $this->s3->listObjects($this->bucket, $continuationToken);
	}

	/**
	 * Retrieves an object from S3.
	 *
	 * @param string $key The object key.
	 * @return object|null The object details or null if not found.
	 */
	public function getObject(string $key): ?object
	{
		return $this->s3->getObject($this->bucket, $key);
	}
}