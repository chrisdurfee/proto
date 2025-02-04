<?php declare(strict_types=1);
namespace Proto\Utils\Files\Disks\Drivers;

use Proto\Http\UploadFile;

/**
 * Driver
 *
 * This will be used as a base driver class.
 *
 * @package Proto\Utils\Files\Disks\Drivers
 * @abstract
 */
abstract class Driver
{
    /**
     * This will set up the driver.
     *
     * @param string|null $bucket
     */
    public function __construct(?string $bucket = null)
    {

    }

    /**
     * This will store a file.
     *
     * @param UploadFile $uploadFile
     * @return bool
     */
    abstract public function store(UploadFile $uploadFile): bool;

    /**
     * This will add a file.
     *
     * @param string $fileName
     * @return bool
     */
    abstract public function add(string $fileName): bool;

    /**
     * This will get a file.
     *
     * @param string $fileName
     * @return string
     */
    abstract public function get(string $fileName): string;

    /**
     * This will get the stored file path.
     *
     * @param string $fileName
     * @return string
     */
    abstract public function getStoredPath(string $fileName): string;

    /**
     * This will download a file.
     *
     * @param string $fileName
     * @return void
     */
    abstract public function download(string $fileName): void;

    /**
     * This will rename a file.
     *
     * @param string $oldFileName
     * @param string $newFileName
     * @return bool
     */
    abstract public function rename(string $oldFileName, string $newFileName): bool;

    /**
     * This will move a file.
     *
     * @param string $oldFileName
     * @param string $newFileName
     * @return bool
     */
    abstract public function move(string $oldFileName, string $newFileName): bool;

    /**
     * This will delete a file.
     *
     * @param string $fileName
     * @return bool
     */
    abstract public function delete(string $fileName): bool;

    /**
     * This will get the file size.
     *
     * @param string $fileName
     * @return int
     */
    abstract public function getSize(string $fileName): int;
}
