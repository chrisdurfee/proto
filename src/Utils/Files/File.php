<?php declare(strict_types=1);
namespace Proto\Utils\Files;

use Proto\Http\UploadFile;
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
	 * Maximum size of a local file that get() will read into memory (100 MB).
	 *
	 * @var int
	 */
	protected const MAX_LOCAL_FILE_SIZE = 100 * 1024 * 1024;

	/**
	 * Retrieves the contents of a file.
	 *
	 * Local files that exceed MAX_LOCAL_FILE_SIZE are rejected to prevent
	 * memory exhaustion from accidentally or maliciously oversized files.
	 *
	 * @param string $path The file path.
	 * @param bool $allowRemote Whether remote files are allowed.
	 * @return string|false The file contents or false on failure.
	 */
	public static function get(string $path, bool $allowRemote = false): string|false
	{
		if (!$allowRemote)
		{
			if (!\file_exists($path))
			{
				return false;
			}

			if (\filesize($path) > static::MAX_LOCAL_FILE_SIZE)
			{
				return false;
			}
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
	 * @return bool
	 */
	public static function checkDir(string $path): bool
	{
		$dir = dirname($path);
		return static::makeDir($dir);
	}

	/**
	 * Creates a directory if it doesn't exist.
	 *
	 * The check-then-create pattern is inherently racy in concurrent environments.
	 * We suppress the mkdir() warning and then re-test is_dir() so that a
	 * concurrent process that won the race still produces a successful result.
	 *
	 * @param string $path The directory path.
	 * @param int $permissions The permissions to set.
	 * @param bool $recursive Whether to create directories recursively.
	 * @return bool True if the directory exists or was created, false on failure.
	 */
	public static function makeDir(string $path, int $permissions = 0755, bool $recursive = true): bool
	{
		if (is_dir($path))
		{
			return true;
		}

		// Suppress the warning; re-check is_dir() to handle TOCTOU races.
		@mkdir($path, $permissions, $recursive);
		return is_dir($path);
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
	 * Ensures the directory exists and is writable.
	 *
	 * @param string $path The file path (not the dir).
	 * @return bool
	 */
	public static function ensureWritableDir(string $path): bool
	{
		$dir = dirname($path);
		if (!is_dir($dir))
		{
			$PERMISSIONS = 0775;
			if (!@mkdir($dir, $PERMISSIONS, true))
			{
				return false;
			}
		}

		// Try to make it writable for owner/group
		if (!is_writable($dir))
		{
			@chmod($dir, 0775);
			clearstatcache(true, $dir);
		}

		return is_writable($dir);
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
		if (!\file_exists($oldFileName))
		{
			return false;
		}

		static::checkDir($newFileName);
		// Fast path: same filesystem
		if (@\rename($oldFileName, $newFileName))
		{
			return true;
		}

		// Cross-device fallback
		if (@\copy($oldFileName, $newFileName))
		{
			@\unlink($oldFileName);
			return true;
		}

		return false;
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
	 * Uses suppressed unlink to avoid TOCTOU race conditions where the
	 * file could be removed between the existence check and the unlink.
	 *
	 * @param string $fileName The file name.
	 * @return bool True on success, false on failure.
	 */
	public static function delete(string $fileName): bool
	{
		if (@\unlink($fileName))
		{
			return true;
		}

		return !\file_exists($fileName);
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
	 * Generates a unique file name for an UploadFile instance.
	 *
	 * @param UploadFile $file The UploadFile instance.
	 * @return string The generated file name.
	 */
	public static function generateFileName(UploadFile $file, string $prefix = ''): string
	{
		$ext = strtolower(pathinfo($file->getOriginalName(), PATHINFO_EXTENSION));
		return uniqid($prefix) . '.' . $ext;
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

		$contentType = static::sanitizeHeaderValue(static::getMimeType($tmpFile));
		if ($contentType)
		{
			header("Content-Type: {$contentType}");
		}

		$fileName = static::sanitizeHeaderValue(static::getName($path));
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

		$mimeType = static::sanitizeHeaderValue(static::getMimeType($path));
		$publicName = static::sanitizeHeaderValue(static::getName($path));

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

	/**
	 * Strips CR/LF characters from a value to prevent HTTP header injection.
	 *
	 * @param string $value The raw header value.
	 * @return string The sanitized header value.
	 */
	protected static function sanitizeHeaderValue(string $value): string
	{
		return str_replace(["\r", "\n", "\0"], '', $value);
	}
}
