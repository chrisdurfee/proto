<?php declare(strict_types=1);
namespace Proto\Utils\Files\Disks\Drivers;

use Proto\Http\UploadFile;

/**
 * Abstract Driver Class
 *
 * Defines a standard interface for file storage drivers.
 *
 * @package Proto\Utils\Files\Disks\Drivers
 * @abstract
 */
abstract class Driver
{
	/**
	 * Initializes the storage driver.
	 *
	 * @param string|null $bucket The storage bucket (optional).
	 */
	public function __construct(
		protected ?string $bucket = null
	) {}

	/**
	 * Stores an uploaded file.
	 *
	 * @param UploadFile $uploadFile The uploaded file object.
	 * @return bool Success status.
	 */
	abstract public function store(UploadFile $uploadFile): bool;

	/**
	 * Adds a file to storage.
	 *
	 * @param string $fileName The file name.
	 * @return bool Success status.
	 */
	abstract public function add(string $fileName): bool;

	/**
	 * Retrieves the contents of a file.
	 *
	 * @param string $fileName The file name.
	 * @return string File contents.
	 */
	abstract public function get(string $fileName): string;

	/**
	 * Retrieves the stored path of a file.
	 *
	 * @param string $fileName The file name.
	 * @return string The stored file path.
	 */
	abstract public function getStoredPath(string $fileName): string;

	/**
	 * Downloads a file.
	 *
	 * @param string $fileName The file name.
	 * @return void
	 */
	abstract public function download(string $fileName): void;

	/**
	 * Renames a file.
	 *
	 * @param string $oldFileName The current file name.
	 * @param string $newFileName The new file name.
	 * @return bool Success status.
	 */
	abstract public function rename(string $oldFileName, string $newFileName): bool;

	/**
	 * Moves a file to a new location.
	 *
	 * @param string $oldFileName The current file name.
	 * @param string $newFileName The new file name.
	 * @return bool Success status.
	 */
	abstract public function move(string $oldFileName, string $newFileName): bool;

	/**
	 * Deletes a file.
	 *
	 * @param string $fileName The file name.
	 * @return bool Success status.
	 */
	abstract public function delete(string $fileName): bool;

	/**
	 * Retrieves the file size.
	 *
	 * @param string $fileName The file name.
	 * @return int File size in bytes.
	 */
	abstract public function getSize(string $fileName): int;
}