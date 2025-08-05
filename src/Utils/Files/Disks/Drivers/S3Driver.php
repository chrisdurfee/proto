<?php declare(strict_types=1);
namespace Proto\Utils\Files\Disks\Drivers;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Proto\Config;
use Proto\Http\UploadFile;

/**
 * Class S3Driver
 *
 * Handles remote file storage operations using AWS S3.
 *
 * @package Proto\Utils\Files\Disks\Drivers
 */
class S3Driver extends Driver
{
	/**
	 * AWS S3 client instance.
	 *
	 * @var S3Client
	 */
	protected S3Client $s3Client;

	/**
	 * S3 bucket name.
	 *
	 * @var string
	 */
	protected string $bucket;

	/**
	 * AWS region.
	 *
	 * @var string
	 */
	protected string $region;

	/**
	 * Optional endpoint.
	 *
	 * @var string|null
	 */
	protected ?string $endpoint = null;

	/**
	 * S3Driver constructor.
	 *
	 * @param string $bucket The bucket alias defined in your config.
	 * @throws \Exception If the configuration is invalid.
	 */
	public function __construct(string $bucket)
	{
		parent::__construct($bucket);

		$settings = $this->getSettings($bucket);
		if ($settings === null)
		{
			throw new \Exception("Invalid AWS S3 settings for bucket: {$bucket}");
		}

		if (empty($settings->key) || empty($settings->secret) || empty($settings->region))
		{
			throw new \Exception("Incomplete AWS S3 configuration for bucket: {$bucket}");
		}

		$this->bucket = $settings->bucketName;
		$this->region = $settings->region;
		$this->endpoint = $settings->endpoint ?? null;

		$config = [
			'version' => $settings->version,
			'region' => $this->region,
			'credentials' => [
				'key' => $settings->key,
				'secret' => $settings->secret,
			],
		];

		if ($this->endpoint)
		{
			$config['endpoint'] = $this->endpoint;
		}

		$this->s3Client = new S3Client($config);
	}

	/**
	 * Retrieves the S3 configuration settings.
	 *
	 * @param string $bucket The bucket alias.
	 * @return object|null
	 */
	protected function getSettings(string $bucket): ?object
	{
		$amazon = Config::access('files')->amazon ?? null;
		if (!$amazon)
		{
			return null;
		}

		$s3 = $amazon->s3 ?? null;
		if (!$s3)
		{
			return null;
		}

		$bucketSettings = $s3->bucket->{$bucket} ?? null;
		if (!$bucketSettings)
		{
			return null;
		}

		$settings = (object)[
			'key' => $s3->credentials->accessKey,
			'secret' => $s3->credentials->secretKey,
			'region' => $bucketSettings->region,
			'version' => $bucketSettings->version,
			'endpoint' => $bucketSettings->endpoint ?? null,
			'bucketName' => $bucketSettings->name,
			'path' => $bucketSettings->path,
		];
		return $settings;
	}

	/**
	 * Stores an uploaded file on S3.
	 *
	 * @param UploadFile $uploadFile The uploaded file object.
	 * @return bool Success status.
	 */
	public function store(UploadFile $uploadFile): bool
	{
		try
		{
			$result = $this->s3Client->putObject([
				'Bucket' => $this->bucket,
				'Key' => $uploadFile->getNewName(),
				'SourceFile' => $uploadFile->getPath(),
				'ACL' => 'public-read',
			]);
			return $result !== null;
		}
		catch (AwsException $e)
		{
			error(
				$e->getMessage(),
				__FILE__,
				__LINE__
			);

			return false;
		}
	}

	/**
	 * Adds a file to S3 from a local path.
	 *
	 * @param string $fileName The file name or path.
	 * @return bool Success status.
	 */
	public function add(string $fileName): bool
	{
		try
		{
			$result = $this->s3Client->putObject([
				'Bucket' => $this->bucket,
				'Key' => basename($fileName),
				'SourceFile' => $fileName,
				'ACL' => 'public-read',
			]);
			return $result !== null;
		}
		catch (AwsException $e)
		{
			return false;
		}
	}

	/**
	 * Retrieves the contents of a file from S3.
	 *
	 * @param string $fileName The file name.
	 * @return string File contents.
	 */
	public function get(string $fileName): string
	{
		try
		{
			$result = $this->s3Client->getObject([
				'Bucket' => $this->bucket,
				'Key' => $fileName,
			]);
			return (string)$result['Body'];
		}
		catch (AwsException $e)
		{
			return '';
		}
	}

	/**
	 * Constructs the public URL of the stored file.
	 *
	 * @param string $fileName The file name.
	 * @return string File URL.
	 */
	public function getStoredPath(string $fileName): string
	{
		if ($this->endpoint)
		{
			return rtrim($this->endpoint, '/') . '/' . $this->bucket . '/' . $fileName;
		}
		return "https://{$this->bucket}.s3.{$this->region}.amazonaws.com/{$fileName}";
	}

	/**
	 * Streams a file for download from S3.
	 *
	 * @param string $fileName The file name.
	 * @return void
	 */
	public function download(string $fileName): void
	{
		try
		{
			$result = $this->s3Client->getObject([
				'Bucket' => $this->bucket,
				'Key' => $fileName,
			]);
			header("Content-Type: " . $result['ContentType']);
			header("Content-Length: " . $result['ContentLength']);
			header("Content-Disposition: attachment; filename=\"" . basename($fileName) . "\"");
			echo $result['Body'];
		}
		catch (AwsException $e)
		{
			http_response_code(404);
			echo "File not found.";
		}
	}

	/**
	 * Renames a file in S3 by copying to a new key and deleting the old one.
	 *
	 * @param string $oldFileName The current file name.
	 * @param string $newFileName The new file name.
	 * @return bool Success status.
	 */
	public function rename(string $oldFileName, string $newFileName): bool
	{
		try
		{
			$this->s3Client->copyObject([
				'Bucket' => $this->bucket,
				'CopySource' => "{$this->bucket}/{$oldFileName}",
				'Key' => $newFileName,
				'ACL' => 'public-read',
			]);
			return $this->delete($oldFileName);
		}
		catch (AwsException $e)
		{
			return false;
		}
	}

	/**
	 * Moves a file in S3, equivalent to renaming it.
	 *
	 * @param string $oldFileName The current file name.
	 * @param string $newFileName The new file name.
	 * @return bool Success status.
	 */
	public function move(string $oldFileName, string $newFileName): bool
	{
		return $this->rename($oldFileName, $newFileName);
	}

	/**
	 * Deletes a file from S3.
	 *
	 * @param string $fileName The file name.
	 * @return bool Success status.
	 */
	public function delete(string $fileName): bool
	{
		try
		{
			$this->s3Client->deleteObject([
				'Bucket' => $this->bucket,
				'Key' => $fileName,
			]);
			return true;
		}
		catch (AwsException $e)
		{
			return false;
		}
	}

	/**
	 * Retrieves the file size in bytes from S3.
	 *
	 * @param string $fileName The file name.
	 * @return int File size in bytes.
	 */
	public function getSize(string $fileName): int
	{
		try
		{
			$result = $this->s3Client->headObject([
				'Bucket' => $this->bucket,
				'Key' => $fileName,
			]);
			return (int)$result['ContentLength'];
		}
		catch (AwsException $e)
		{
			return 0;
		}
	}
}
