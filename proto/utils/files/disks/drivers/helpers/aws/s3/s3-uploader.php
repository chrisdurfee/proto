<?php declare(strict_types=1);
namespace Proto\Utils\Files\Disks\Drivers\Helpers\Aws\S3;

use Aws\S3\Exception\S3Exception;
use Aws\S3\ObjectUploader;
use Aws\S3\S3Client;

/**
 * S3Uploader
 *
 * This will handle the s3 uploader.
 *
 * @package Proto\Utils\Files\Disks\Drivers\S3
 */
class S3Uploader
{
    /**
     * This will hold the object uploader instance.
     *
     * @var ObjectUploader $uploader
     */
    protected ObjectUploader $uploader;

    /**
     * This constructs the s3 uploader.
     *
     * @param S3Client $s3Client
     * @param string $tmpName
     * @param string $bucket
     * @param string $destinationPath
     * @return void
     */
    public function __construct(
        S3Client $s3Client,
        string $tmpName,
        string $bucket,
        string $destinationPath
    )
    {
        $content = $this->openFile($tmpName);
        $this->uploader = new ObjectUploader(
			$s3Client,
			$bucket,
			$destinationPath,
			$content
        );
    }

    /**
	 * This will open the file.
	 *
	 * @param string $tmpName
	 * @return resource
	 */
	protected function openFile(string $tmpName)
	{
		return fopen($tmpName, 'rb');
	}

    /**
     * This will upload the file to s3.
     *
     * @return bool
     */
    public function send(): bool
    {
        try
		{
            $SUCCESS_STATUS_CODE = 200;
			$result = $this->uploader->upload();
			if (!$result || $result["@metadata"]["statusCode"] !== $SUCCESS_STATUS_CODE)
			{
                return false;
            }

            return true;
		}
		catch (S3Exception $e)
		{
            error(
                'Error: ' . $e->getAwsErrorMessage(),
                __FILE__,
                __LINE__
            );
		}

		return false;
    }
}

