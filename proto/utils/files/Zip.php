<?php declare(strict_types=1);
namespace Proto\Utils\Files;

use Proto\Utils\Files\File;
use ZipArchive;

/**
 * Zip
 *
 * Handles the creation and management of zip archives.
 *
 * @package Proto\Utils\Files
 */
class Zip
{
	/**
	 * Creates a zip archive.
	 *
	 * @param string|array $files List of files to include in the archive.
	 * @param string $archiveName Name of the zip archive.
	 * @return string|false The archive file name on success, false on failure.
	 */
	public static function archive(string|array $files, string $archiveName = 'temp'): string|false
	{
		$files = self::formatFiles($files);
		$zip = self::open($archiveName);

		if (!self::addFiles($zip, $files))
		{
			self::close($zip);
			return false;
		}

		self::close($zip);
		return $archiveName . '.zip';
	}

	/**
	 * Formats the input into an array of file paths.
	 *
	 * @param string|array $files
	 * @return array
	 */
	protected static function formatFiles(string|array $files): array
	{
		return is_array($files) ? $files : [$files];
	}

	/**
	 * Opens a zip archive.
	 *
	 * @param string $archiveName
	 * @return ZipArchive
	 */
	protected static function open(string $archiveName): ZipArchive
	{
		$zip = new ZipArchive();
		$zip->open($archiveName . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
		return $zip;
	}

	/**
	 * Closes the zip archive.
	 *
	 * @param ZipArchive $zip
	 * @return void
	 */
	protected static function close(ZipArchive $zip): void
	{
		$zip->close();
	}

	/**
	 * Adds files to the zip archive.
	 *
	 * @param ZipArchive $zip
	 * @param array $files
	 * @return bool True if files were added successfully, false otherwise.
	 */
	protected static function addFiles(ZipArchive $zip, array $files): bool
	{
		foreach ($files as $file)
		{
			$customName = null;
			$fileName = is_object($file) ? ($file->url ?? '') : $file;
			$customName = is_object($file) ? ($file->customName ?? null) : null;

			$contents = File::get($fileName, true);
			if ($contents === false)
			{
				return false;
			}

			$fileName = self::getS3FileName($fileName) ?? self::getBasename($fileName);

			if (!empty($customName))
			{
				$fileName = self::addExtension($fileName, $customName);
			}

			$zip->addFromString($fileName, $contents);
		}

		return true;
	}

	/**
	 * Ensures the custom name has the correct file extension.
	 *
	 * @param string $fileName
	 * @param string $customName
	 * @return string
	 */
	protected static function addExtension(string $fileName, string $customName): string
	{
		$extension = pathinfo($fileName, PATHINFO_EXTENSION);
		return $customName . '.' . $extension;
	}

	/**
	 * Extracts the file name from an S3 URL, if applicable.
	 *
	 * @param string $url
	 * @return string|null
	 */
	protected static function getS3FileName(string $url): ?string
	{
		return preg_match('/([\w\-]+\.\w{3,4})$/', $url, $matches) ? $matches[0] : null;
	}

	/**
	 * Extracts the file name from a file path.
	 *
	 * @param string $path
	 * @return string
	 */
	protected static function getBasename(string $path): string
	{
		$search = 'fileName=';
		$index = strpos($path, $search);

		if ($index === false)
		{
			return basename($path);
		}

		return substr($path, $index + strlen($search));
	}
}