<?php declare(strict_types=1);
namespace Proto\Utils\Files\Disks\Drivers;

use Proto\Config;
use Proto\Utils\Files\File;
use Proto\Http\UploadFile;

/**
 * LocalDriver
 *
 * Handles local file storage operations.
 *
 * @package Proto\Utils\Files\Disks\Drivers
 */
class LocalDriver extends Driver
{
	/**
	 * @var string $path The storage path.
	 */
	protected string $path;

	/**
	 * Initializes the local driver.
	 *
	 * @param string $bucket The bucket name.
	 */
	public function __construct(string $bucket)
	{
		parent::__construct($bucket);
		$this->initializeBucket($bucket);
	}

	/**
	 * Retrieves the local storage settings for the given bucket.
	 *
	 * @param string $bucket The bucket name.
	 * @return object|null Storage settings or null if not found.
	 */
	protected function getSettings(string $bucket): ?object
	{
		$local = Config::access('files')->local ?? null;
		return $local->{$bucket} ?? $local ?? null;
	}

	/**
	 * Initializes and validates the bucket configuration.
	 *
	 * @param string $bucket The bucket name.
	 * @return void
	 * @throws \Exception If bucket settings are missing.
	 */
	protected function initializeBucket(string $bucket): void
	{
		$settings = $this->getSettings($bucket);
		if ($settings === null || empty($settings->path))
		{
			throw new \Exception("Invalid local storage settings for bucket: {$bucket}");
		}

		$this->path = rtrim($settings->path, '/') . '/';
	}

	/**
	 * Retrieves the absolute storage path.
	 *
	 * @return string The absolute path.
	 */
	protected function getPath(): string
	{
		return BASE_PATH . $this->path;
	}

	/**
	 * Generates the full file path.
	 *
	 * @param string $fileName The file name.
	 * @param bool $extractName Whether to extract the base file name.
	 * @return string The full file path.
	 */
	protected function getFilePath(string $fileName, bool $extractName = true): string
	{
		$name = $extractName ? File::getName($fileName) : $fileName;
		return $this->getPath() . $name;
	}

	/**
	 * Stores an uploaded file.
	 *
	 * @param UploadFile $uploadFile The uploaded file.
	 * @return bool Success status.
	 */
	public function store(UploadFile $uploadFile): bool
	{
		$path = $this->getFilePath($uploadFile->getNewName(), false);
		return $uploadFile->move($path);
	}

	/**
	 * Adds a file to storage.
	 *
	 * @param string $fileName The file name.
	 * @return bool Success status.
	 */
	public function add(string $fileName): bool
	{
		return File::move($fileName, $this->getFilePath($fileName));
	}

	/**
	 * Retrieves the contents of a file.
	 *
	 * @param string $fileName The file name.
	 * @return string File contents.
	 */
	public function get(string $fileName): string
	{
		return File::get($this->getFilePath($fileName));
	}

	/**
	 * Retrieves the stored file path.
	 *
	 * @param string $fileName The file name.
	 * @return string The full stored file path.
	 */
	public function getStoredPath(string $fileName): string
	{
		return $this->getFilePath($fileName);
	}

	/**
	 * Streams a file to the browser for download.
	 *
	 * @param string $fileName The file name.
	 * @return void
	 */
	public function download(string $fileName): void
	{
		File::stream($this->getStoredPath($fileName));
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
		$newPath = $this->getFilePath($newFileName, false);
		return File::rename($this->getFilePath($oldFileName, false), $newPath);
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
		$oldPath = $this->getFilePath($oldFileName, false);
		return File::move($oldPath, $this->getFilePath($newFileName, false));
	}

	/**
	 * Deletes a file.
	 *
	 * @param string $fileName The file name.
	 * @return bool Success status.
	 */
	public function delete(string $fileName): bool
	{
		return File::delete($this->getStoredPath($fileName));
	}

	/**
	 * Retrieves the file size.
	 *
	 * @param string $fileName The file name.
	 * @return int The file size in bytes.
	 */
	public function getSize(string $fileName): int
	{
		return File::getSize($this->getStoredPath($fileName));
	}
}