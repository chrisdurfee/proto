<?php declare(strict_types=1);
namespace Proto\Utils;

/**
 * File
 *
 * This will handle the file utils.
 *
 * @package Proto\Utils
 */
class File extends Util
{
    /**
     * This will return the contents of a file.
     *
     * @param string $path
     * @return string|bool
     */
	public static function get(string $path)
	{
        if (!\file_exists($path))
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
        $dir = dirname($path);
        if (!is_dir($dir))
        {
            mkdir($dir, 0755, true);
        }
        else
        {
            chmod($dir, 0755);
        }

        $result = \file_put_contents($path, $contents);
        return ($result !== false);
	}

    /**
     * This will get the file mime type.
     *
     * @param string $path
     * @return string
     */
    public static function getMimeType(string $path): string
    {
        $finfo = \finfo_open(FILEINFO_MIME_TYPE);
		return \finfo_file($finfo, $path);
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
     * This will get the file name from a path.
     *
     * @param string $path
     * @return string
     */
    public static function getName(string $path): string
    {
        return \basename($path);
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

        if($unlink === true)
        {
            unlink($path);
        }

		exit;
	}
}
