<?php declare(strict_types=1);
namespace Proto\Utils\Files\Disks\Drivers\Helpers\Aws\S3;

/**
 * S3Credentials
 *
 * This will handle the s3 credentials.
 *
 * @package Proto\Utils\Files\Disks\Drivers\S3
 */
class S3Credentials
{
    /**
     * This will set up the s3 credentials.
     *
     * @param string $key
     * @param string $secret
     * @return void
     */
    public function __construct(
        public readonly string $key,
        public readonly string $secret
    )
    {
        $this->key = $key;
        $this->secret = $secret;
    }
}