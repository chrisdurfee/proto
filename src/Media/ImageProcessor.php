<?php declare(strict_types=1);
namespace Proto\Media;

use Imagick;
use ImagickPixel;
use Proto\Utils\Files\Vault;

/**
 * ImageProcessor
 *
 * Generic Imagick-based image optimization pipeline for uploads
 * (avatars, covers, post media, entity media, etc.).
 *
 * Responsibilities:
 *  - Defend against decompression bombs via Imagick resource limits and a
 *    megapixel cap.
 *  - Re-encode the original to a sane max dimension and modern format,
 *    stripping EXIF / color profiles. May rename the file if the input
 *    format isn't browser-friendly (e.g. HEIC → WebP); the caller is
 *    responsible for persisting the returned `mainFile`.
 *  - Generate any number of named variants (thumb, card, large, …) using a
 *    declarative preset list.
 *  - Clean up variant files on replace/delete.
 *
 * Optional `$options['onRemoteWrite']` callable receives
 * `(string $bucket, string $filename, string $mimeType)` after each remote
 * object is uploaded — apps can use this for CDN Cache-Control hooks.
 *
 * @package Proto\Media
 * @suppresswarnings PHP0413
 */
class ImageProcessor
{
	/**
	 * Maximum input megapixels we'll decode. Anything larger is rejected as
	 * a likely decompression bomb. ~80 MP covers any real phone/DSLR.
	 */
	public const MAX_INPUT_MEGAPIXELS = 80;

	/**
	 * Default lossy quality when callers don't override.
	 */
	public const DEFAULT_QUALITY = 82;

	/**
	 * Default cap applied when re-encoding the original (longest edge, px).
	 */
	public const DEFAULT_ORIGINAL_MAX_DIM = 2048;

	/**
	 * @return bool
	 */
	public static function isSupported(): bool
	{
		return extension_loaded('imagick');
	}

	/**
	 * Process an already-stored image: optionally re-encode the original,
	 * then generate the requested variants beside it on the same disk + bucket.
	 *
	 * @param string $disk Vault disk name (e.g. 'local').
	 * @param string $bucket Vault bucket (e.g. 'users', 'posts').
	 * @param string $filename Stored filename (basename) of the original.
	 * @param array<int, array<string, mixed>> $presets Variant preset list.
	 *   Each preset:
	 *     - name (string, required)
	 *     - mode ('square'|'fit'|'width', default 'fit')
	 *     - width / height / size (int) depending on mode
	 *     - quality (int, optional)
	 *     - suffix (string, optional, defaults to "_{name}")
	 * @param array<string, mixed> $options
	 *     - reencodeOriginal (bool, default true)
	 *     - originalMaxDim (int)
	 *     - originalQuality (int, default 85)
	 *     - variantFormat ('webp'|'jpeg'|'auto', default 'auto')
	 *     - onRemoteWrite (callable|null) invoked after remote uploads
	 * @return array<string, mixed>|null
	 *   On success:
	 *     [
	 *       'mainFile' => 'abc.webp',
	 *       'variants' => ['thumb' => 'abc_thumb.webp', ...],
	 *       'width'    => int,
	 *       'height'   => int,
	 *       'mimeType' => string,
	 *       'fileSize' => int,
	 *     ]
	 *   On failure: null. Already-written variant files are cleaned up.
	 */
	public static function process(
		string $disk,
		string $bucket,
		string $filename,
		array $presets,
		array $options = []
	): ?array
	{
		if (!self::isSupported())
		{
			return null;
		}

		$diskHandle = Vault::disk($disk, $bucket);
		$isLocal = $disk === 'local';
		$onRemoteWrite = $options['onRemoteWrite'] ?? null;

		$sourcePath = $isLocal
			? DiskScratch::localPath($diskHandle, $filename)
			: DiskScratch::fetchToTmp($diskHandle, $filename, 'img');

		if ($sourcePath === null || !is_file($sourcePath))
		{
			error_log('[ImageProcessor] source missing: ' . $filename);
			return null;
		}

		self::applyResourceLimits();

		$variantFormat = self::resolveVariantFormat($options['variantFormat'] ?? 'auto');
		$inputExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		$mainFile = $filename;
		$writtenVariants = [];
		$base = null;
		$createdMainFile = null;
		$scratchFiles = $isLocal ? [] : [$sourcePath];

		try
		{
			$base = new Imagick($sourcePath);

			if (!self::withinMegapixelLimit($base))
			{
				return null;
			}

			$base->autoOrient();

			$reencodeOriginal = (bool)($options['reencodeOriginal'] ?? true);
			$originalMime = self::mimeFromExtension($inputExt);
			$originalSize = @filesize($sourcePath) ?: null;
			$originalWidth = $base->getImageWidth();
			$originalHeight = $base->getImageHeight();

			if ($reencodeOriginal)
			{
				$mainFormat = self::pickOriginalFormat($inputExt);
				$mainExt = ($mainFormat === 'jpeg') ? 'jpg' : $mainFormat;
				$mainFile = pathinfo($filename, PATHINFO_FILENAME) . '.' . $mainExt;
				$mainPath = DiskScratch::targetPath($diskHandle, $isLocal, $mainFile, 'img');

				$maxDim = (int)($options['originalMaxDim'] ?? self::DEFAULT_ORIGINAL_MAX_DIM);
				$quality = (int)($options['originalQuality'] ?? 85);

				$work = clone $base;
				try
				{
					self::fitMaxDimension($work, $maxDim);
					self::writeImage($work, $mainPath, $mainFormat, $quality);
					$originalMime = self::mimeForFormat($mainFormat);
					if (!$isLocal)
					{
						$diskHandle->add($mainPath);
						$scratchFiles[] = $mainPath;
						self::invokeRemoteWrite($onRemoteWrite, $bucket, $mainFile, $originalMime);
					}
					$originalWidth = $work->getImageWidth();
					$originalHeight = $work->getImageHeight();
					$originalSize = @filesize($mainPath) ?: null;
					$createdMainFile = ($mainFile !== $filename) ? $mainFile : null;
				}
				finally
				{
					$work->clear();
				}

				if ($mainFile !== $filename)
				{
					self::removeStoredFile($diskHandle, $filename, $isLocal);
				}

				/**
				 * Re-open from the (possibly converted) main file so variants
				 * inherit the EXIF strip and orientation correction.
				 */
				$base->clear();
				$base = new Imagick($mainPath);
				$base->autoOrient();
			}

			$stem = pathinfo($mainFile, PATHINFO_FILENAME);
			$variantExt = $variantFormat;

			foreach ($presets as $preset)
			{
				$name = (string)($preset['name'] ?? '');
				if ($name === '')
				{
					continue;
				}
				$suffix = (string)($preset['suffix'] ?? ('_' . $name));
				$variantName = $stem . $suffix . '.' . $variantExt;
				$variantPath = DiskScratch::targetPath($diskHandle, $isLocal, $variantName, 'img');

				$variant = clone $base;
				try
				{
					self::applyPreset($variant, $preset);
					$quality = (int)($preset['quality'] ?? self::DEFAULT_QUALITY);
					self::writeImage($variant, $variantPath, $variantFormat, $quality);
					if (!$isLocal)
					{
						$diskHandle->add($variantPath);
						$scratchFiles[] = $variantPath;
						self::invokeRemoteWrite(
							$onRemoteWrite,
							$bucket,
							$variantName,
							self::mimeForFormat($variantFormat)
						);
					}
					$writtenVariants[$name] = $variantName;
				}
				finally
				{
					$variant->clear();
				}
			}

			return [
				'mainFile' => $mainFile,
				'variants' => $writtenVariants,
				'width' => $originalWidth,
				'height' => $originalHeight,
				'mimeType' => $originalMime,
				'fileSize' => $originalSize,
			];
		}
		catch (\Throwable $e)
		{
			error_log('[ImageProcessor] ' . $e->getMessage() . ' file=' . $filename);
			self::deleteVariants($disk, $bucket, $writtenVariants);
			if ($createdMainFile !== null)
			{
				self::removeStoredFile($diskHandle, $createdMainFile, $isLocal);
			}
			return null;
		}
		finally
		{
			if ($base instanceof Imagick)
			{
				$base->clear();
			}

			if (!$isLocal)
			{
				DiskScratch::cleanup($scratchFiles);
			}
		}
	}

	/**
	 * Delete a map of variant files. Missing files are ignored.
	 *
	 * @param string $disk
	 * @param string $bucket
	 * @param array<string, mixed>|null $variants
	 * @return void
	 */
	public static function deleteVariants(string $disk, string $bucket, ?array $variants): void
	{
		if ($variants === null || $variants === [])
		{
			return;
		}

		$diskHandle = Vault::disk($disk, $bucket);
		$isLocal = $disk === 'local';
		foreach ($variants as $value)
		{
			if (!is_string($value) || $value === '')
			{
				continue;
			}
			self::removeStoredFile($diskHandle, $value, $isLocal);
		}
	}

	/**
	 * Delete a primary file and all of its variants.
	 *
	 * @param string $disk
	 * @param string $bucket
	 * @param string|null $mainFile
	 * @param array<string, mixed>|null $variants
	 * @return void
	 */
	public static function deleteMainAndVariants(
		string $disk,
		string $bucket,
		?string $mainFile,
		?array $variants
	): void
	{
		if ($mainFile !== null && $mainFile !== '')
		{
			self::removeStoredFile(Vault::disk($disk, $bucket), $mainFile, $disk === 'local');
		}
		self::deleteVariants($disk, $bucket, $variants);
	}

	/**
	 * @param callable|null $callback
	 * @param string $bucket
	 * @param string $filename
	 * @param string $mimeType
	 * @return void
	 */
	private static function invokeRemoteWrite(?callable $callback, string $bucket, string $filename, string $mimeType): void
	{
		if ($callback === null)
		{
			return;
		}

		try
		{
			$callback($bucket, $filename, $mimeType);
		}
		catch (\Throwable $e)
		{
			error_log('[ImageProcessor] onRemoteWrite failed: ' . $e->getMessage());
		}
	}

	/**
	 * Delete a stored file by its REAL on-disk path.
	 *
	 * LocalDriver::delete() routes through getStoredPath(), which strips the
	 * file extension (pathinfo PATHINFO_FILENAME). For files that have an
	 * extension it therefore targets the wrong path. We rebuild the true path
	 * and unlink it directly when local.
	 *
	 * @param object $diskHandle
	 * @param string $fileName
	 * @param bool $isLocal
	 * @return void
	 */
	private static function removeStoredFile(object $diskHandle, string $fileName, bool $isLocal = true): void
	{
		if ($fileName === '')
		{
			return;
		}

		if (!$isLocal)
		{
			$diskHandle->delete($fileName);
			return;
		}

		$path = DiskScratch::localPath($diskHandle, $fileName);
		if (is_file($path))
		{
			@unlink($path);
		}
	}

	/**
	 * @param Imagick $image
	 * @param array<string, mixed> $preset
	 * @return void
	 */
	private static function applyPreset(Imagick $image, array $preset): void
	{
		$mode = (string)($preset['mode'] ?? 'fit');

		switch ($mode)
		{
			case 'square':
				$size = (int)($preset['width'] ?? $preset['size'] ?? 256);
				$image->cropThumbnailImage($size, $size);
				break;

			case 'width':
				$maxW = (int)($preset['width'] ?? 800);
				self::fitMaxWidth($image, $maxW);
				break;

			case 'fit':
			default:
				$w = (int)($preset['width'] ?? 0);
				$h = (int)($preset['height'] ?? $w);
				if ($w > 0 && $h > 0)
				{
					$image->thumbnailImage($w, $h, true);
				}
				break;
		}
	}

	/**
	 * @param Imagick $image
	 * @param int $maxWidth
	 * @return void
	 */
	private static function fitMaxWidth(Imagick $image, int $maxWidth): void
	{
		$w = $image->getImageWidth();
		if ($w <= $maxWidth)
		{
			return;
		}
		$h = $image->getImageHeight();
		$newH = (int)max(1, round($h * ($maxWidth / $w)));
		$image->resizeImage($maxWidth, $newH, Imagick::FILTER_LANCZOS, 1, true);
	}

	/**
	 * @param Imagick $image
	 * @param int $maxDim Longest edge.
	 * @return void
	 */
	private static function fitMaxDimension(Imagick $image, int $maxDim): void
	{
		$w = $image->getImageWidth();
		$h = $image->getImageHeight();
		$longest = max($w, $h);
		if ($longest <= $maxDim)
		{
			return;
		}
		$scale = $maxDim / $longest;
		$newW = (int)max(1, round($w * $scale));
		$newH = (int)max(1, round($h * $scale));
		$image->resizeImage($newW, $newH, Imagick::FILTER_LANCZOS, 1, true);
	}

	/**
	 * Strip metadata, flatten alpha for JPEG, and write the image.
	 *
	 * @param Imagick $image
	 * @param string $absolutePath
	 * @param string $format 'webp', 'jpeg', or 'png'.
	 * @param int $quality
	 * @return void
	 */
	private static function writeImage(Imagick $image, string $absolutePath, string $format, int $quality): void
	{
		$image->stripImage();

		if ($format === 'jpeg' || $format === 'jpg')
		{
			if ($image->getImageAlphaChannel() !== Imagick::ALPHACHANNEL_UNDEFINED
				&& $image->getImageAlphaChannel() !== Imagick::ALPHACHANNEL_OFF)
			{
				$image->setImageBackgroundColor(new ImagickPixel('white'));
				$image->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
			}
			$image->setImageFormat('jpeg');
			$image->setImageCompressionQuality($quality);
		}
		elseif ($format === 'png')
		{
			$image->setImageFormat('png');
			$pngQuality = (int)max(0, min(9, round((100 - $quality) / 10)));
			$image->setImageCompressionQuality($pngQuality * 10);
		}
		else
		{
			$image->setImageFormat('webp');
			$image->setImageCompressionQuality($quality);
		}

		$image->writeImage($absolutePath);

		// chmod is best-effort: on some filesystems it raises EPERM even when
		// the process owns the file. Proto's error handler does not respect
		// the `@` suppression operator, so a temporary no-op handler is used.
		$previousHandler = set_error_handler(static function (): bool
		{
			return true;
		});

		try
		{
			chmod($absolutePath, 0644);
		}
		finally
		{
			set_error_handler($previousHandler);
		}
	}

	/**
	 * @param string $inputExt
	 * @return string 'webp', 'jpeg', or 'png'
	 */
	private static function pickOriginalFormat(string $inputExt): string
	{
		$ext = strtolower($inputExt);

		if ($ext === 'png')
		{
			return 'png';
		}
		if ($ext === 'gif')
		{
			return 'jpeg';
		}
		if (in_array($ext, ['jpg', 'jpeg'], true))
		{
			return 'jpeg';
		}
		if ($ext === 'webp')
		{
			return 'webp';
		}

		return self::supportsWebp() ? 'webp' : 'jpeg';
	}

	/**
	 * @param string $option
	 * @return string 'webp' or 'jpeg'
	 */
	private static function resolveVariantFormat(string $option): string
	{
		$option = strtolower($option);
		if ($option === 'jpeg' || $option === 'jpg')
		{
			return 'jpeg';
		}
		if ($option === 'webp')
		{
			return self::supportsWebp() ? 'webp' : 'jpeg';
		}
		return self::supportsWebp() ? 'webp' : 'jpeg';
	}

	/**
	 * @return bool
	 */
	private static function supportsWebp(): bool
	{
		static $cache = null;
		if ($cache === null)
		{
			$cache = in_array('WEBP', Imagick::queryFormats(), true);
		}
		return $cache;
	}

	/**
	 * @param string $format
	 * @return string
	 */
	private static function mimeForFormat(string $format): string
	{
		return match ($format)
		{
			'jpeg', 'jpg' => 'image/jpeg',
			'png' => 'image/png',
			default => 'image/webp',
		};
	}

	/**
	 * @param string $ext
	 * @return string
	 */
	private static function mimeFromExtension(string $ext): string
	{
		return match (strtolower($ext))
		{
			'jpg', 'jpeg' => 'image/jpeg',
			'png' => 'image/png',
			'gif' => 'image/gif',
			'webp' => 'image/webp',
			'heic' => 'image/heic',
			'heif' => 'image/heif',
			'avif' => 'image/avif',
			default => 'application/octet-stream',
		};
	}

	/**
	 * @param Imagick $image
	 * @return bool
	 */
	private static function withinMegapixelLimit(Imagick $image): bool
	{
		$w = $image->getImageWidth();
		$h = $image->getImageHeight();
		$mp = ($w * $h) / 1_000_000;
		return $mp <= self::MAX_INPUT_MEGAPIXELS;
	}

	/**
	 * Apply Imagick resource limits to defend against decompression bombs.
	 *
	 * @return void
	 */
	private static function applyResourceLimits(): void
	{
		static $applied = false;
		if ($applied)
		{
			return;
		}
		$applied = true;

		try
		{
			Imagick::setResourceLimit(Imagick::RESOURCETYPE_MEMORY, 512 * 1024 * 1024);
			Imagick::setResourceLimit(Imagick::RESOURCETYPE_MAP, 1024 * 1024 * 1024);
			Imagick::setResourceLimit(Imagick::RESOURCETYPE_DISK, 2 * 1024 * 1024 * 1024);
			Imagick::setResourceLimit(Imagick::RESOURCETYPE_AREA, 128_000_000);
			Imagick::setResourceLimit(Imagick::RESOURCETYPE_THREAD, 2);
		}
		catch (\Throwable)
		{
			// Older Imagick builds may not expose all limits; ignore silently.
		}
	}
}
