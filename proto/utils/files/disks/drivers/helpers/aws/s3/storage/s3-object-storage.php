<?php declare(strict_types=1);
namespace Proto\Utils\Files\Disks\Drivers\Helpers\Aws\S3\Storage;

use Proto\Utils\Files\Disks\Drivers\Helpers\Aws\AwsStorage as Storage;

/**
 * S3ObjectStorage
 *
 * This will handle the s3 object storage.
 *
 * @package Proto\Utils\Files\Disks\Drivers\Helpers\Aws\S3\Storage
 */
class S3ObjectStorage extends Storage
{
    /**
     * This will get a file by file name.
     *
     * @param string $fileName
     * @return object|null
     */
    public function getByName(string $fileName): ?object
    {
        return $this->select()
            ->where('file_name = ?')
            ->first([$fileName]);
    }
}