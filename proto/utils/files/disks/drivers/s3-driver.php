<?php declare(strict_types=1);
namespace Proto\Utils\Files\Disks\Drivers;

include_once __DIR__ . '/../../../../../vendor/autoload.php';

use Proto\Utils\Files\Disks\Drivers\Helpers\Aws\S3\S3Integration;
use Proto\Utils\File;
use Proto\Http\UploadFile;

/**
 * S3Driver
 *
 * This will handle the s3 driver.
 *
 * @package Proto\Utils\Files\Disks\Drivers
 */
class S3Driver extends Driver
{
	/**
	 * @var string $bucket This will hold the bucket.
	 */
    protected string $bucket;

	/**
	 * @var string $path This will hold the path.
	 */
	protected string $path;

	/**
	 * This will hold the s3 client instance.
	 *
	 * @var S3Integration $s3 This will hold the s3 client instance.
	 */
	protected S3Integration $s3;

	/**
	 * This will hold the UploadFile.
	 *
	 * @var UploadFile $uploadFile This will hold the UploadFile.
	 */
	protected UploadFile $uploadFile;

	/**
     * This will set up the s3 driver.
	 *
     * @param string $bucket This will hold the bucket.
	 * @return void
     */
    public function __construct(string $bucket)
    {
        parent::__construct($bucket);

		if (!isset($bucket))
        {
            $this->error('No bucket was set.');
        }

        $this->setup($bucket);
    }

	/**
     * This will setup S3.
     *
     * @param string $bucketName This will hold the bucket.
     * @return void
     */
    protected function setup(string $bucketName): void
    {
		$s3 = env('files')->amazon->s3 ?? null;
		if (!isset($s3))
		{
			$this->error('No S3 configurations found.');
		}

        $config = $s3->bucket->{$bucketName} ?? null;
		if (!isset($config))
		{
			$this->error("No S3 configurations found for bucket: {$bucketName}.");
		}

		$keys = $s3->credentials ?? null;
		if (!isset($keys))
		{
			$this->error("No S3 credentials fouund.");
		}

		$this->bucket = $config->name ?? null;
        $this->path = $config->path ?? null;
		$this->s3 = new S3Integration($keys, $config);
    }

	/**
	 * This will handle errors.
	 *
	 * @param string $message
	 * @return void
	 */
	protected function error(string $message): void
	{
		error(
			'Error: ' . $message,
			__FILE__,
			__LINE__
		);
		die;
	}

	/**
     * This will store a file.
     *
     * @param UploadFile $uploadFile
     * @return bool
     */
    public function store(UploadFile $uploadFile): bool
	{
		$fileName = $uploadFile->getFilePath();
		return $this->add($fileName);
	}

	/**
     * This will add a file.
     *
     * @param string $fileName
     * @return bool
     */
    public function add(string $fileName): bool
	{
		return $this->s3->upload($fileName, $this->path, $this->bucket);
	}

    /**
     * This will get a file.
     *
     * @param string $fileName
     * @return string
     */
    public function get(string $fileName): string
	{
		$result = $this->s3->getFileUrl($fileName);
		return ($result) ? $result : '';
	}

	/**
     * This will download a file.
     *
     * @param string $fileName
     * @return void
     */
    public function download(string $fileName): void
	{
		$this->get($fileName);
		$this->stream($fileName);
	}

	/**
	 * This will stream a file.
	 *
	 * @param string $fileName
	 * @return void
	 */
	protected function stream(string $fileName): void
	{
		File::stream($fileName);
	}

    /**
     * This will delete a file.
     *
     * @param string $fileName
     * @return bool
     */
    public function delete(string $fileName): bool
	{
		$key = $this->path . $fileName;
		return $this->s3->delete($this->bucket, $key);
	}

	/**
     * This will get the stored file path.
     *
     * @param string $fileName
     * @return string
     */
    public function getStoredPath(string $fileName): string
	{
		$key = $this->path . $fileName;
		return $this->s3->getObjectUrl($this->bucket, $key);
	}

	/**
     * This will rename a file.
     *
     * @param string $oldFileName
     * @param string $newFileName
     * @return bool
     */
    public function rename(string $oldFileName, string $newFileName): bool
	{
		return false;
	}

    /**
     * This will move a file.
     *
     * @param string $oldFileName
     * @param string $newFileName
     * @return bool
     */
    public function move(string $oldFileName, string $newFileName): bool
	{
		return false;
	}

	/**
     * This will get the file size.
     *
     * @param string $fileName
     * @return int
     */
    public function getSize(string $fileName): int
	{
		return 0;
	}

	/**
	 * This will list objects in a bucket.
	 *
	 * @param string|null $continuationToken
	 * @return object
	 */
	public function listObjects(?string $continuationToken = null): object
	{
		return $this->s3->listObjects($this->bucket, $continuationToken);
	}

	/**
	 * This will get an object.
	 *
	 * @param string $key
	 * @return object|null
	 */
	public function getObject(string $key): ?object
	{
		return $this->s3->getObject($this->bucket, $key);
	}
}
