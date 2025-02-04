<?php declare(strict_types=1);
namespace Proto\Utils\Files;

use Proto\Utils\Files\File;

/**
 * Zip
 *
 * This will handle zip archives.
 *
 * @package Proto\Utils\Files
 */
class Zip
{
    /**
     * This will create a zip archive.
     *
     * @param string|array $files
     * @return string|bool
     */
	public static function archive($files, string $archiveName = 'temp')
	{
        $files = static::formatFiles($files);
        $zip = static::open($archiveName);
        if (!static::addFiles($zip, $files))
        {
            return false;
        }

        static::close($zip);
        return $archiveName . '.zip';
    }

    /**
     * This will flormat the files array.
     *
     * @param array|object $files
     * @return array
     */
    protected static function formatFiles($files): array
    {
        if (\gettype($files) === 'array')
        {
            return $files;
        }

        return [$files];
    }

    /**
     * This will open a zip archive.
     *
     * @param string $archiveName
     * @return \ZipArchive
     */
    protected static function open(string $archiveName): \ZipArchive
    {
        $zip = new \ZipArchive();
        $zip->open($archiveName . '.zip', \ZipArchive::CREATE);
        return $zip;
    }

    /**
     * This will close the zip archive.
     *
     * @param \ZipArchive $zip
     * @return void
     */
    protected static function close(\ZipArchive $zip): void
    {
        $zip->close();
    }

    /**
     * This will add the files to the zip archive.
     *
     * @param \ZipArchive $zip
     * @param array $files
     * @return bool
     */
    protected static function addFiles(\ZipArchive $zip, array $files): bool
    {
        foreach ($files as $file)
        {
            $customName = null;
            $fileName = $file;
            if (is_object($file))
            {
                $customName = $file->customName ?? null;
                $fileName = $file->url;
            }

            $contents = File::get($fileName, true);
            if (!$contents)
            {
                return false;
            }

            $fileName = static::getS3FileName($fileName) ?? static::getBasename($fileName);
            if (isset($customName) && !empty($customName))
            {
                $fileName = static::addExtension($fileName, $customName);
            }

            $zip->addFromString($fileName, $contents);
        }

        return true;
    }

    /**
     * This will ensure the
     * custom name has the correct extension.
     *
     * @param string $fileName
     * @param string $customName
     * @return string
     */
    protected static function addExtension(string $fileName, string $customName): string
    {
        $extension = \pathinfo($fileName, PATHINFO_EXTENSION);
        return $customName . '.' . $extension;
    }

    /**
     * This will get the file name from an
     * s3 file url or return null if the match
     * isn't made.
     *
     * @param string $url
     * @return string|null
     */
    protected static function getS3FileName(string $url): ?string
    {
        return preg_match('/(\w+\-\w*\.\w{3})/', $url, $matches) ? $matches[0] : null;
    }

    /**
     * Temporary function until the vault util
     * is finished so I can access the s3 bucket
     */
    protected static function getBasename(string $path)
    {
        $search = 'fileName=';
        if (!$index = \strpos($path, $search))
        {
            return \basename($path);
        }

        $index += \strlen($search);
        return \substr($path, $index);
    }
}