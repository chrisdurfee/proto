<?php declare(strict_types=1);
namespace Proto\Utils\Files\Disks\Drivers\Helpers\Aws\S3\Models;

use Proto\Utils\Files\Disks\Drivers\Helpers\Aws\S3\Storage\S3ObjectStorage;
use Proto\Models\Model;

/**
 * S3Object
 *
 * This will handle the s3 object model.
 *
 * @package Proto\Utils\Files\Disks\Drivers\Helpers\Aws\S3\Models
 */
class S3Object extends Model
{
    /**
     * @var string $tableName
     */
    protected static $tableName = 's3_objects';

    /**
     * @var string $alias
     */
    protected static $alias = 'so';

    /**
     * @var array $fields
     */
    protected static $fields = [
        'id',
        'createdAt',
        'updatedAt',
		'fileName',
		'objectKey',
		'bucket',
        'url'
    ];

    /**
     * @var bool $passModel
     */
    protected $passModel = true;

    /**
     * @var string $storageType
     */
    protected static $storageType = S3ObjectStorage::class;

}