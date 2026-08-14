<?php declare(strict_types=1);
namespace Proto\Media;

/**
 * DiskScratch
 *
 * Shared remote-disk scratch-file plumbing for media processors that need
 * a real local filesystem path (Imagick, ffmpeg/ffprobe) regardless of
 * whether the Vault disk is `local` or a remote S3-compatible driver.
 *
 * On `local`, callers get the real on-disk path directly (no copy). On a
 * remote disk, source files are downloaded to a unique scratch directory
 * first, and output files are written to a scratch path so they can be
 * uploaded via `$diskHandle->add()` afterward. Callers own their own
 * try/finally around {@see cleanup()} — this class never deletes
 * anything on its own.
 *
 * @package Proto\Media
 */
class DiskScratch
{
	/**
	 * Real on-disk path for a stored local-disk file. Works around
	 * `LocalDriver::getStoredPath()` stripping the extension via
	 * `pathinfo(..., PATHINFO_FILENAME)`, by rebuilding the path from
	 * the bucket directory + the original filename.
	 *
	 * @param object $diskHandle
	 * @param string $filename
	 * @return string
	 */
	public static function localPath(object $diskHandle, string $filename): string
	{
		return dirname($diskHandle->getStoredPath($filename)) . '/' . $filename;
	}

	/**
	 * Download a remote-disk object to a local scratch file. Returns null
	 * (and leaves nothing behind) on failure.
	 *
	 * @param object $diskHandle
	 * @param string $filename
	 * @param string $prefix Scratch directory prefix — namespaces tmp
	 *   dirs per caller (e.g. 'img' vs 'video') for easier debugging.
	 * @return string|null Local scratch path.
	 */
	public static function fetchToTmp(object $diskHandle, string $filename, string $prefix = 'media'): ?string
	{
		$data = $diskHandle->get($filename);
		if ($data === '' || $data === false)
		{
			return null;
		}

		$scratchPath = self::scratchPath($filename, $prefix);
		if (@file_put_contents($scratchPath, $data) === false)
		{
			return null;
		}

		return $scratchPath;
	}

	/**
	 * Build the write target for a given output filename: the real
	 * on-disk path for `local`, or a unique local scratch path (to be
	 * uploaded via `$diskHandle->add()`) for a remote disk.
	 *
	 * @param object $diskHandle
	 * @param bool $isLocal
	 * @param string $filename
	 * @param string $prefix
	 * @return string
	 */
	public static function targetPath(object $diskHandle, bool $isLocal, string $filename, string $prefix = 'media'): string
	{
		return $isLocal
			? self::localPath($diskHandle, $filename)
			: self::scratchPath($filename, $prefix);
	}

	/**
	 * Unique local scratch file path for a given stored filename,
	 * preserving the original basename so remote `->add()` (which keys
	 * objects off `basename($localPath)`) uploads under the right name.
	 *
	 * @param string $filename
	 * @param string $prefix
	 * @return string
	 */
	public static function scratchPath(string $filename, string $prefix = 'media'): string
	{
		$dir = rtrim(sys_get_temp_dir(), '/') . '/' . $prefix . '-' . bin2hex(random_bytes(8));
		if (!is_dir($dir))
		{
			@mkdir($dir, 0700, true);
		}
		return $dir . '/' . basename($filename);
	}

	/**
	 * Delete scratch files and their per-call scratch directories.
	 * Missing files and non-empty/shared directories are ignored
	 * silently (best-effort cleanup only).
	 *
	 * @param array<int, string> $paths
	 * @return void
	 */
	public static function cleanup(array $paths): void
	{
		foreach (array_unique($paths) as $path)
		{
			if ($path !== '' && is_file($path))
			{
				@unlink($path);
			}
			@rmdir(dirname($path));
		}
	}
}
