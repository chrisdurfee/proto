<?php declare(strict_types=1);
namespace Proto\Utils\Files\Disks\Drivers\Helpers\Aws\Cloudfront\Models;

use Proto\Utils\Files\Disks\Drivers\Helpers\Aws\Cloudfront\Storage\CloudfrontSignedUrlStorage;
use Proto\Models\Model;

/**
 * CloudfrontSignedUrl
 *
 * This will handle the cloudfront signed url model.
 *
 * @package Proto\Utils\Files\Disks\Drivers\Helpers\Aws\Cloudfront\Models
 */
class CloudfrontSignedUrl extends Model
{
    /**
     * @var string $tableName
     */
    protected static $tableName = 'cloudfront_signed_urls';

    /**
     * @var string $alias
     */
    protected static $alias = 'csu';

    /**
     * @var array $fields
     */
    protected static $fields = [
        'id',
		's3ObjectId',
		'signedUrl',
		'expires',
        'createdAt',
        'updatedAt'
    ];

    /**
     * @var bool $passModel
     */
    protected $passModel = true;

    /**
     * @var string $storageType
     */
    protected static $storageType = CloudfrontSignedUrlStorage::class;

}