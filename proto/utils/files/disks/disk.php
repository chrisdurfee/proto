<?php declare(strict_types=1);
namespace Proto\Utils\Files\Disks;

use Proto\Utils\Files\Disks\Drivers;

/**
 * Disk
 *
 * This will create a new disk driver.
 *
 * @package Proto\Utils\Files\Disks
 */
class Disk
{
    /**
     *
     * @var object $driver This will hold the driver.
     */
    protected object $driver;

    /**
     * This will set up the driver.
     *
     * @param string $driver
     * @param string|null $bucket
     */
    public function __construct(
        string $driver = 'local',
        ?string $bucket = null
    )
    {
        $this->driver = $this->getDriver($driver, $bucket);
    }

    /**
     * This will get the disk driver.
     *
     * @param string $driver
     * @param string|null $bucket
     * @return object
     */
    protected function getDriver(string $driver, ?string $bucket = null): object
    {
        switch ($driver)
        {
            case 's3':
                return new Drivers\S3Driver($bucket);
            default:
                return new Drivers\LocalDriver($bucket);
        }
    }

    /**
     * This will store a file.
     *
     * @param object $uploadFile
     * @return bool
     */
    public function store(object $uploadFile): bool
    {
        return $this->driver->store($uploadFile);
    }

    /**
     * This will add a file.
     *
     * @param string $fileName
     * @return bool
     */
	public function add(string $fileName): bool
	{
        return $this->driver->add($fileName);
    }

    /**
     * This will return the contents of a file.
     *
     * @param string $fileName
     * @return string
     */
	public function get(string $fileName): string
	{
        return $this->driver->get($fileName);
    }

    /**
     * This will get the stored path.
     *
     * @param string $fileName
     * @return string
     */
	public function getStoredPath(string $fileName): string
	{
        return $this->driver->getStoredPath($fileName);
    }

    /**
     * This will download the contents of a file.
     *
     * @param string $fileName
     * @return void
     */
	public function download(string $fileName): void
	{
        $this->driver->download($fileName);
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
        return $this->driver->rename($oldFileName, $newFileName);
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
        return $this->driver->move($oldFileName, $newFileName);
    }

    /**
     * This will delete a file.
     *
     * @param string $fileName
     * @return bool
     */
	public function delete(string $fileName): bool
	{
        return $this->driver->delete($fileName);
    }

    /**
     * This will get the file size.
     *
     * @param string $fileName
     * @return int
     */
    public function getSize(string $fileName): int
    {
        return $this->driver->getSize($fileName);
    }
}
