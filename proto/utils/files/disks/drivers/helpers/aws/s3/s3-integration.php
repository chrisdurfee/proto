<?php declare(strict_types=1);
namespace Proto\Utils\Files\Disks\Drivers\Helpers\Aws\S3;

use Proto\Utils\Files\Disks\Drivers\Helpers\Aws\Cloudfront\CloudfrontIntegration;
use Proto\Utils\Files\Disks\Drivers\Helpers\Aws\S3\Controllers\S3ObjectController;
use Proto\Utils\Files\File;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

/**
 * S3Integration
 *
 * This will handle making requests to S3.
 *
 * @package Proto\Utils\Files\Disks\Drivers\S3
 */
class S3Integration
{
    /**
     * This holds the S3Client instance.
     *
     * @var S3Client $s3
     */
    protected S3Client $s3;

    /**
     * This holds the S3ObjectController.
     *
     * @var S3ObjectController $objectController
     */
    protected S3ObjectController $objectController;

    /**
     * This holds the CloudfrontIntegration instance.
     *
     * @var CloudfrontIntegration $cloudfront
     */
    protected CloudfrontIntegration $cloudfront;

    /**
     * This sets up the S3Client.
     *
     * @param object $credentials
     * @param object $config
     * @return void
     */
    public function __construct(object $credentials, object $config)
    {
        $this->objectController = new S3ObjectController();
        $this->cloudfront = new CloudfrontIntegration();

        $this->s3 = new S3Client([
			'credentials' => [
				'key'    => $credentials->accessKey,
				'secret' => $credentials->secretKey
			],
			'region'  => $config->region,
			'version' => $config->version
		]);
    }

    /**
     * This will upload a file.
     *
     * @param string $tmpName
     * @param string $path
     * @param string $bucket
     * @return bool
     */
    public function upload(
        string $tmpName,
        string $s3Path,
        string $bucket
    ): bool
	{
        $fileName = File::getName($tmpName);
		$destinationPath = $s3Path . $fileName;
		$uploader = new S3Uploader($this->s3, $tmpName, $bucket, $destinationPath);
        $result = $uploader->send();
        if (!$result)
        {
            return false;
        }

        $url = $this->getObjectUrl($bucket, $destinationPath);
        if (!$url)
        {
            return false;
        }

        $save = $this->objectController->add((object)[
            'fileName' => $fileName,
            'objectKey' => $destinationPath,
            'bucket' => $bucket,
            'url' => $url
        ]);
        return $save->success;
	}

    /**
     * This will get a file url.
     *
     * @param string $fileName
     * @return string|bool
     */
    public function getFileUrl(string $fileName): string|bool
    {
        $file = $this->objectController->getByName($fileName);
        if (!isset($file))
        {
            return false;
        }

        if (!static::isBucketSecure($file))
        {
            /**
             * If the url is in the db, return it.
             */
            if (isset($file->url) && !empty($file->url))
            {
                return $file->url;
            }

            return $this->getObjectUrl($file->bucket, $file->objectKey);
        }

        return $this->cloudfront->getSignedUrl($file);
    }

    /**
     * This will determine if the file's bucket is secure.
     *
     * @param object $file
     * @return boolean
     */
    protected static function isBucketSecure(object $file): bool
    {
        $bucketName = $file->bucket ?? null;
        if (!isset($bucketName))
        {
            return false;
        }

        $result = env('files')->amazon->s3->bucket->{$bucketName} ?? null;
        if (!isset($result))
        {
            return false;
        }

        return $result->secure ?? false;
    }

    /**
     * This will request a file delete.
     *
     * @param string $fileName
     * @return bool
     */
    public function delete(string $bucket, string $key): bool
	{
		try
		{
			$this->s3->deleteObject([
				'Bucket' => $bucket,
				'Key' => $key
			]);

			return $this->verifyDelete($bucket, $key);
		}
		catch (S3Exception $e)
		{
			return $this->error($e);
		}
	}

    /**
     * This will request the url for an object.
     *
     * @param string $bucket
     * @param string $key
     * @return string|bool
     */
    public function getObjectUrl(string $bucket, string $key): string|bool
    {
        try
        {
            return $this->s3->getObjectUrl($bucket, $key);
        }
        catch (S3Exception $e)
        {
            return $this->error($e);
        }
    }

    /**
	 * This will request an object and verify
     * it no longer exists.
	 *
	 * @param string $bucket
     * @param string $key
	 * @return bool
	 */
	protected function verifyDelete(string $bucket, string $key): bool
	{
		try
		{
			$this->s3->getObject([
				'Bucket' => $bucket,
				'Key' => $key
			]);

			return false;
		}
		catch (S3Exception $e)
		{
            if ($e->getAwsErrorCode() === 'NoSuchKey')
            {
                return true;
            }
			return $this->error($e);
		}
	}

    /**
     * This will list objects in a bucket.
     *
     * @param string $bucket
     * @param string|null $continuationToken
     * @return object
     */
    public function listObjects(string $bucket, ?string $continuationToken = null): ?object
    {
        try
        {
            $results = $this->s3->listObjectsV2([
                'ContinuationToken' => $continuationToken,
                'Bucket' => $bucket
            ]);

            // Return data
            return (object)[
                'contents' => $results->get('Contents'),
                'continuationToken' => $results->get('NextContinuationToken')
            ];
        }
        catch (S3Exception $e)
        {
            $this->error($e);
            return null;
        }
    }

    /**
     * This will get an object.
     *
     * @param string $bucket
     * @param string $key
     * @return object|null
     */
    public function getObject(string $bucket, string $key): ?object
    {
        try
        {
            $result = $this->s3->getObject([
                'Bucket' => $bucket,
                'Key' => $key
            ]);

            return (object)[
                'object' => $result->get('@metadata')
            ];
        }
        catch (S3Exception $e)
        {
            $this->error($e);
            return null;
        }
    }

    /**
     * This will report an error from an S3Exception.
     *
     * @param S3Exception $e
     * @return bool
     */
    protected function error(S3Exception $e): bool
    {
        error(
            'Error: ' . $e->getAwsErrorMessage(),
            __FILE__,
            __LINE__
        );
        return false;
    }
}