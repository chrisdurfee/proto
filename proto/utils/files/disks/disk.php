<?php declare(strict_types=1);

namespace Proto\Utils\Files\Disks;

use Proto\Utils\Files\Disks\Drivers\LocalDriver;
use Proto\Utils\Files\Disks\Drivers\S3Driver;

/**
 * Disk
 *
 * Manages file storage using different drivers (local, S3, etc.).
 *
 * @package Proto\Utils\Files\Disks
 */
class Disk
{
	/**
	 * @var object $driver Storage driver instance.
	 */
	protected object $driver;

	/**
	 * Initializes the disk with the appropriate driver.
	 *
	 * @param string $driver The storage driver type.
	 * @param string|null $bucket The storage bucket (optional).
	 */
	public function __construct(
		string $driver = 'local',
		?string $bucket = null
	) {
		$this->driver = self::getDriverInstance($driver, $bucket);
	}

	/**
	 * Returns an instance of the specified driver.
	 *
	 * @param string $driver The storage driver type.
	 * @param string|null $bucket The storage bucket (optional).
	 * @return object The storage driver instance.
	 */
	protected static function getDriverInstance(string $driver, ?string $bucket = null): object
	{
		return match ($driver) {
			's3' => new S3Driver($bucket),
			default => new LocalDriver($bucket),
		};
	}

	/**
	 * Stores an uploaded file.
	 *
	 * @param object $uploadFile The uploaded file object.
	 * @return bool Success status.
	 */
	public function store(object $uploadFile): bool
	{
		return $this->driver->store($uploadFile);
	}

	/**
	 * Adds a file to storage.
	 *
	 * @param string $fileName The file name.
	 * @return bool Success status.
	 */
	public function add(string $fileName): bool
	{
		return $this->driver->add($fileName);
	}

	/**
	 * Retrieves the contents of a file.
	 *
	 * @param string $fileName The file name.
	 * @return string File contents.
	 */
	public function get(string $fileName): string
	{
		return $this->driver->get($fileName);
	}

	/**
	 * Retrieves the stored path of a file.
	 *
	 * @param string $fileName The file name.
	 * @return string The stored file path.
	 */
	public function getStoredPath(string $fileName): string
	{
		return $this->driver->getStoredPath($fileName);
	}

	/**
	 * Downloads a file.
	 *
	 * @param string $fileName The file name.
	 * @return void
	 */
	public function download(string $fileName): void
	{
		$this->driver->download($fileName);
	}

	/**
	 * Renames a file.
	 *
	 * @param string $oldFileName The current file name.
	 * @param string $newFileName The new file name.
	 * @return bool Success status.
	 */
	public function rename(string $oldFileName, string $newFileName): bool
	{
		return $this->driver->rename($oldFileName, $newFileName);
	}

	/**
	 * Moves a file to a new location.
	 *
	 * @param string $oldFileName The current file name.
	 * @param string $newFileName The new file name.
	 * @return bool Success status.
	 */
	public function move(string $oldFileName, string $newFileName): bool
	{
		return $this->driver->move($oldFileName, $newFileName);
	}

	/**
	 * Deletes a file.
	 *
	 * @param string $fileName The file name.
	 * @return bool Success status.
	 */
	public function delete(string $fileName): bool
	{
		return $this->driver->delete($fileName);
	}

	/**
	 * Retrieves the file size.
	 *
	 * @param string $fileName The file name.
	 * @return int File size in bytes.
	 */
	public function getSize(string $fileName): int
	{
		return $this->driver->getSize($fileName);
	}
}