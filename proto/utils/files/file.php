<?php declare(strict_types=1);
namespace Proto\Utils\Files;

use Proto\Utils\Util;

/**
 * File
 *
 * This will handle files.
 *
 * @package Proto\Utils\Files
 */
class File extends Util
{
    /**
     * This will return the contents of a file.
     *
     * @param string $path
     * @param bool $allowRemote
     * @return string|bool
     */
	public static function get(string $path, bool $allowRemote = false)
	{
        if ($allowRemote === false && !\file_exists($path))
        {
            return false;
        }

		return \file_get_contents($path);
    }

    /**
     * This will return the contents of a file.
     *
     * @param string $path
     * @param string $contents
     * @return bool
     */
	public static function put(string $path, string $contents): bool
	{
        static::checkDir($path);

        $result = \file_put_contents($path, $contents);
        return ($result !== false);
	}

    /**
     * This will check if a directory exists and create it if it does not.
     *
     * @param string $path
     * @return void
     */
    public static function checkDir(string $path): void
    {
        $PERMISSIONS = 0755;
        $dir = dirname($path);
        if (!is_dir($dir))
        {
            mkdir($dir, $PERMISSIONS, true);
        }
        else
        {
            chmod($dir, $PERMISSIONS);
        }
    }

    /**
     * This will get the file name from a path.
     *
     * @param string $path
     * @return string|null
     */
    public static function getName(string $path): ?string
    {
        $baseName = \basename($path);
        $parts = \explode("?", $baseName);
        return $parts[0] ?? null;
    }

    /**
	 * This will create a unique new file name to stop
	 * upload conflicts.
	 *
     * @param string $fileName
	 * @return string
	 */
	public static function createNewName(string $fileName): string
	{
		$parts = \explode(".", $fileName);
		$ext = end($parts);

		$microTimeStamp = \round(\microtime(true)) . '-' . rand(0, 10000000);
		return "{$microTimeStamp}.{$ext}";
	}

    /**
     * This will rename a file.
     *
     * @param string $oldFileName
     * @param string $newFileName
     * @return bool
     */
	public static function rename(string $oldFileName, string $newFileName): bool
	{
        if (!\file_exists($oldFileName))
        {
            return false;
        }

		return \rename($oldFileName, $newFileName);
    }

    /**
     * This will move a file.
     *
     * @param string $oldFileName
     * @param string $newFileName
     * @return bool
     */
	public static function move(string $oldFileName, string $newFileName): bool
	{
        return static::rename($oldFileName, $newFileName);
    }

    /**
     * This will delete a file.
     *
     * @param string $fileName
     * @return bool
     */
	public static function delete(string $fileName): bool
	{
        if (!\file_exists($fileName))
        {
            return false;
        }

		return \unlink($fileName);
    }

    /**
     * This will copy a file.
     *
     * @param string $path
     * @return bool
     */
	public static function copy(string $file, string $newFile): bool
	{
        if (!\file_exists($file))
        {
            return false;
        }

		return \copy($file, $newFile);
    }

    /**
     * This will get the file mime type.
     *
     * @param string $path
     * @return string|bool
     */
    public static function getMimeType(string $path): string|bool
    {
        $parts = \explode("?", $path);
        $path = $parts[0];

        $finfo = \finfo_open(FILEINFO_MIME_TYPE);
		$result = \finfo_file($finfo, $path);
        \finfo_close($finfo);
        return $result;
    }

    /**
     * This will get the size of a file.
     *
     * @param string $fileName
     * @return int
     */
    public static function getSize(string $fileName): int
    {
        return \filesize($fileName);
    }

    /**
     * This will create a new tmp file name.
     *
     * @param string $prefix
     * @return string|bool
     */
    public static function createTmpName(string $prefix = 'proto'): string|bool
    {
        $tmpDir = sys_get_temp_dir();
        return tempnam($tmpDir, $prefix);
    }

    /**
     * This will download a file.
     *
     * @param string $path
     * @return void
     */
    public static function download(string $path): void
    {
        $ALLOW_REMOTE = true;
        $content = static::get($path, $ALLOW_REMOTE);
        if (empty($content))
        {
            return;
        }

        /**
         * We need to create a local tmp file to get the file mimie type to allow
         * the download to work.
         */
        $tmpFile = static::createTmpName();
        static::put($tmpFile, $content);

        /**
         * This will add the file content type to the download.
         */
        $contentType = static::getMimeType($tmpFile);
        if ($contentType)
        {
            header("Content-Type: {$contentType}");
        }

        /**
         * Setting the Content-Disposition header to prompt for download.
         */
        $fileName = static::getName($path);
        header("Content-Disposition: attachment; filename=\"{$fileName}\"");
        header('Content-Length: ' . strlen($content));

        echo $content;

        /**
         * We need to remove the tmp file.
         */
        unlink($tmpFile);
        die;
    }

    /**
	 * This will render the file to stream to the browser.
	 *
	 * @param string $path
     * @param bool $unlink
	 * @return void
	 */
	public static function stream(string $path, bool $unlink = false): void
	{
		if (!\is_file($path))
		{
			return;
		}

		// get the file's mime type to send the correct content type header
		$mimeType = static::getMimeType($path);
        $publicName = static::getName($path);

		// send the headers
		header("Content-Disposition: attachment; filename={$publicName};");
		header("Content-Type: {$mimeType}");
		header('Content-Length: ' . static::getSize($path));

		// stream the file
		$fp = fopen($path, 'rb');
		fpassthru($fp);

        if ($unlink === true)
        {
            unlink($path);
        }

		exit;
	}
}
