<?php declare(strict_types=1);
namespace Proto\Utils\Files;

use Proto\Utils\Util;

/**
 * File
 *
 * Handles file operations such as reading, writing, deleting, and streaming.
 *
 * @package Proto\Utils\Files
 */
class File extends Util
{
	/**
	 * Retrieves the contents of a file.
	 *
	 * @param string $path The file path.
	 * @param bool $allowRemote Whether remote files are allowed.
	 * @return string|false The file contents or false on failure.
	 */
	public static function get(string $path, bool $allowRemote = false): string|false
	{
		if (!$allowRemote && !\file_exists($path))
		{
			return false;
		}

		return \file_get_contents($path) ?: false;
	}

	/**
	 * Writes contents to a file.
	 *
	 * @param string $path The file path.
	 * @param string $contents The contents to write.
	 * @return bool True on success, false on failure.
	 */
	public static function put(string $path, string $contents): bool
	{
		static::checkDir($path);

		return (\file_put_contents($path, $contents) !== false);
	}

	/**
	 * Ensures the directory exists; creates it if necessary.
	 *
	 * @param string $path The file path.
	 * @return void
	 */
	public static function checkDir(string $path): void
	{
		$dir = dirname($path);
		if (!is_dir($dir))
		{
            $PERMISSIONS = 0755;
			mkdir($dir, $PERMISSIONS, true);
		}
	}

	/**
	 * Retrieves the file name from a given path.
	 *
	 * @param string $path The file path.
	 * @return string|null The file name or null if not found.
	 */
	public static function getName(string $path): ?string
	{
		return pathinfo($path, PATHINFO_FILENAME);
	}

	/**
	 * Generates a unique file name to prevent upload conflicts.
	 *
	 * @param string $fileName The original file name.
	 * @return string The new unique file name.
	 */
	public static function createNewName(string $fileName): string
	{
		$ext = pathinfo($fileName, PATHINFO_EXTENSION);
		return uniqid() . '.' . $ext;
	}

	/**
	 * Renames a file.
	 *
	 * @param string $oldFileName The current file name.
	 * @param string $newFileName The new file name.
	 * @return bool True on success, false on failure.
	 */
	public static function rename(string $oldFileName, string $newFileName): bool
	{
		return \file_exists($oldFileName) ? \rename($oldFileName, $newFileName) : false;
	}

	/**
	 * Moves a file.
	 *
	 * @param string $oldFileName The current file name.
	 * @param string $newFileName The new file name.
	 * @return bool True on success, false on failure.
	 */
	public static function move(string $oldFileName, string $newFileName): bool
	{
		return static::rename($oldFileName, $newFileName);
	}

	/**
	 * Deletes a file.
	 *
	 * @param string $fileName The file name.
	 * @return bool True on success, false on failure.
	 */
	public static function delete(string $fileName): bool
	{
		return \file_exists($fileName) ? \unlink($fileName) : false;
	}

	/**
	 * Copies a file.
	 *
	 * @param string $file The source file.
	 * @param string $newFile The destination file.
	 * @return bool True on success, false on failure.
	 */
	public static function copy(string $file, string $newFile): bool
	{
		return \file_exists($file) ? \copy($file, $newFile) : false;
	}

	/**
	 * Retrieves the MIME type of a file.
	 *
	 * @param string $path The file path.
	 * @return string|false The MIME type or false on failure.
	 */
	public static function getMimeType(string $path): string|false
	{
		if (!\file_exists($path))
		{
			return false;
		}

		$finfo = \finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = \finfo_file($finfo, $path);
		\finfo_close($finfo);

		return $mimeType ?: false;
	}

	/**
	 * Retrieves the file size.
	 *
	 * @param string $fileName The file name.
	 * @return int The file size in bytes.
	 */
	public static function getSize(string $fileName): int
	{
		return \file_exists($fileName) ? \filesize($fileName) : 0;
	}

	/**
	 * Generates a temporary file name.
	 *
	 * @param string $prefix The file prefix.
	 * @return string|false The temporary file name or false on failure.
	 */
	public static function createTmpName(string $prefix = 'proto'): string|false
	{
		return \tempnam(sys_get_temp_dir(), $prefix);
	}

	/**
	 * Handles file downloads.
	 *
	 * @param string $path The file path.
	 * @return void
	 */
	public static function download(string $path): void
	{
		$content = static::get($path, true);
		if (!$content)
		{
			return;
		}

		$tmpFile = static::createTmpName();
		static::put($tmpFile, $content);

		$contentType = static::getMimeType($tmpFile);
		if ($contentType)
		{
			header("Content-Type: {$contentType}");
		}

		$fileName = static::getName($path);
		header("Content-Disposition: attachment; filename=\"{$fileName}\"");
		header('Content-Length: ' . strlen($content));

		echo $content;
		unlink($tmpFile);
		exit;
	}

	/**
	 * Streams a file to the browser.
	 *
	 * @param string $path The file path.
	 * @param bool $unlink Whether to delete the file after streaming.
	 * @return void
	 */
	public static function stream(string $path, bool $unlink = false): void
	{
		if (!\is_file($path))
		{
			return;
		}

		$mimeType = static::getMimeType($path);
		$publicName = static::getName($path);

		header("Content-Disposition: attachment; filename={$publicName};");
		header("Content-Type: {$mimeType}");
		header('Content-Length: ' . static::getSize($path));

		readfile($path);

		if ($unlink)
		{
			unlink($path);
		}

		exit;
	}
}
