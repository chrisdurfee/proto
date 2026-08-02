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
	 * Optional object ACL (e.g. "public-read"). Left unset by default since
	 * R2 and ACL-disabled AWS buckets reject any ACL header; set
	 * files.amazon.s3.bucket.{alias}.acl to opt in for buckets that need it.
	 *
	 * @var string|null
	 */
	protected ?string $acl = null;

	/**
	 * Bucket-relative key prefix (e.g. "users/"), read from
	 * files.amazon.s3.bucket.{alias}.path. Every S3 key must be built
	 * through {@see key()} so uploads land under their category folder
	 * instead of the bucket root.
	 *
	 * @var string
	 */
	protected string $path = '';

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
		$this->acl = $settings->acl ?? null;
		$this->path = (string)($settings->path ?? '');

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
			'path' => $bucketSettings->path ?? '',
			'acl' => $bucketSettings->acl ?? null,
		];
		return $settings;
	}

	/**
	 * Builds the real S3 object key for a bare filename by applying this
	 * bucket's configured path prefix. Every method below must route
	 * filenames through this instead of using them as the Key directly.
	 *
	 * @param string $fileName
	 * @return string
	 */
	protected function key(string $fileName): string
	{
		return $this->path . $fileName;
	}

	/**
	 * Builds the putObject/copyObject params array, including the ACL
	 * entry only when one is configured (R2 and ACL-disabled AWS buckets
	 * reject any ACL header).
	 *
	 * @param array $params
	 * @return array
	 */
	protected function withAcl(array $params): array
	{
		if ($this->acl !== null)
		{
			$params['ACL'] = $this->acl;
		}
		return $params;
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
			$result = $this->s3Client->putObject($this->withAcl([
				'Bucket' => $this->bucket,
				'Key' => $this->key($uploadFile->getNewName()),
				'SourceFile' => $uploadFile->getPath(),
			]));
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
			$result = $this->s3Client->putObject($this->withAcl([
				'Bucket' => $this->bucket,
				'Key' => $this->key(basename($fileName)),
				'SourceFile' => $fileName,
			]));
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
				'Key' => $this->key($fileName),
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
			return rtrim($this->endpoint, '/') . '/' . $this->bucket . '/' . $this->key($fileName);
		}
		return "https://{$this->bucket}.s3.{$this->region}.amazonaws.com/" . $this->key($fileName);
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
				'Key' => $this->key($fileName),
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
			$this->s3Client->copyObject($this->withAcl([
				'Bucket' => $this->bucket,
				'CopySource' => "{$this->bucket}/" . $this->key($oldFileName),
				'Key' => $this->key($newFileName),
			]));
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
				'Key' => $this->key($fileName),
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
				'Key' => $this->key($fileName),
			]);
			return (int)$result['ContentLength'];
		}
		catch (AwsException $e)
		{
			return 0;
		}
	}
}
