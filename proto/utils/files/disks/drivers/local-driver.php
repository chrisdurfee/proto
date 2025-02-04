<?php declare(strict_types=1);
namespace Proto\Utils\Files\Disks\Drivers;

use Proto\Config;
use Proto\Utils\Files\File;
use Proto\Http\UploadFile;

/**
 * LocalDriver
 *
 * This will handle the local driver.
 *
 * @package Proto\Utils\Files\Disks\Drivers
 */
class LocalDriver extends Driver
{
    /**
     * @var string $path This will hold the path.
     */
    protected string $path;

	/**
	 * This will set up the local driver.
     *
	 * @param string $bucket
     * @return void
	 */
	public function __construct(string $bucket)
	{
        parent::__construct($bucket);
		$this->setBucket($bucket);
	}

	/**
	 * This will get the local settings from the bucket.
	 *
	 * @param string $bucket
	 * @return object|null
	 */
	protected function getSettings(string $bucket): ?object
	{
		$local = Config::access('files')->local ?? null;
        if (isset($local) === false)
        {
            return null;
        }

		return $local->{$bucket} ?? $local ?? null;
	}

	/**
	 * This will set the bucket.
	 *
	 * @param string $bucket
	 * @return void
	 */
	protected function setBucket(string $bucket): void
	{
		if (isset($bucket) === false)
		{
			throw new \Exception("The bucket was not set.");
		}

		$settings = $this->getSettings($bucket);
		if (isset($settings) === false)
		{
			throw new \Exception("No local settings are located under the bucket: {$bucket}");
		}

		$this->path = $settings->path ?? null;
	}

    /**
     * This will get the path.
     *
     * @return string
     */
    protected function getPath(): string
    {
        return BASE_PATH . $this->path;
    }

    /**
     * This will get the file path.
     *
     * @param string $fileName
     * @return string
     */
    protected function getFilePath(string $fileName, bool $getFileName = true): string
    {
        $name = ($getFileName === true)? File::getName($fileName) : $fileName;
        return $this->getPath() . $name;
    }

    /**
     * This will store a file.
     *
     * @param UploadFile $uploadFile
     * @return bool
     */
    public function store(UploadFile $uploadFile): bool
    {
        $path = $this->getFilePath($uploadFile->getNewName(), false);
        return $uploadFile->move($path);
    }

	/**
     * This will add a file.
     *
     * @param string $fileName
     * @return bool
     */
    public function add(string $fileName): bool
    {
        return File::move($fileName, $this->getFilePath($fileName));
    }

    /**
     * This will get a file.
     *
     * @param string $fileName
     * @return string
     */
    public function get(string $fileName): string
    {
        return File::get($fileName);
    }

    /**
     * This will get the stored file path.
     *
     * @param string $fileName
     * @return string
     */
    public function getStoredPath(string $fileName): string
	{
        return $this->getFilePath($fileName);
	}

    /**
     * This will download a file.
     *
     * @param string $fileName
     * @return void
     */
    public function download(string $fileName): void
    {
        $path = $this->getStoredPath($fileName);
        File::stream($path);
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
        $newPath = $this->getFilePath($newFileName, false);
        return File::rename($oldFileName, $newPath);
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
        $oldPath = $this->getFilePath($oldFileName, false);
        return File::move($oldPath, $newFileName);
    }

    /**
     * This will delete a file.
     *
     * @param string $fileName
     * @return void
     */
    public function delete(string $fileName): bool
    {
        return File::delete($fileName);
    }

    /**
     * This will get the file size.
     *
     * @param string $fileName
     * @return int
     */
    public function getSize(string $fileName): int
    {
        return File::getSize($fileName);
    }
}
