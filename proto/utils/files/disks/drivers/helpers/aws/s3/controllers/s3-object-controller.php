<?php declare(strict_types=1);
namespace Proto\Utils\Files\Disks\Drivers\Helpers\Aws\S3\Controllers;

use Proto\Utils\Files\Disks\Drivers\Helpers\Aws\S3\Models\S3Object;
use Proto\Controllers\ModelController as Controller;

/**
 * S3ObjectController
 *
 * This will handle the s3 object controller.
 *
 * @package Proto\Utils\Files\Disks\Drivers\Helpers\Aws\S3\Controllers
 */
class S3ObjectController extends Controller
{
    /**
     * @var bool $passResponse
     */
    protected $passResponse = true;

    /**
     * This will setup the model class.
     *
     * @param string|null $modelClass by using the magic constant ::class
     */
    public function __construct(
        protected ?string $modelClass = S3Object::class
    )
    {
        parent::__construct($modelClass);
    }

    /**
     * This will get the s3 object by the file name.
     *
     * @param string $fileName
     * @return object|null
     */
    public function getByName(string $fileName): ?object
    {
        return $this->modelClass::getByName($fileName);
    }
}