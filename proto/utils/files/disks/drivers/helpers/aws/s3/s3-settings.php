<?php declare(strict_types=1);
namespace Proto\Utils\Files\Disks\Drivers\Helpers\Aws\S3;

/**
 * S3Settings
 *
 * This will handle the s3 settings.
 *
 * @package Proto\Utils\Files\Disks\Drivers\S3
 */
class S3Settings
{
    /**
     * This will hold the credentials.
     *
     * @var string $credentials
     */
    public readonly string $credentials;

    /**
     * This will set the region.
     *
     * @var string $region
     */
    public readonly string $region;

    /**
     * This will set the version.
     *
     * @var string $version
     */
    public readonly string $version;

    /**
     * This will set up the s3 settings.
     *
     * @return void
     */
    public function __construct()
    {
        $this->setConfigs();
    }

    /**
     * This will set the configs.
     *
     * @return void
     */
    protected function setConfigs(): void
    {
        /**
         * This will get the s3 config.
         */
        $s3 = env('files')->amazon->s3 ?? null;
        if (isset($s3) === false)
        {
            throw new \Exception("The s3 config was not set.");
        }

        /**
         * This will set the credentials.
         */
        $this->credentials = new S3Credentials(
            $s3->credentials->accessKey,
            $s3->credentials->secretKey
        );

        /**
         * This will set the region and version.
         */
        $this->region = $s3->region;
        $this->version = $s3->version;
    }
}