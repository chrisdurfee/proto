<?php declare(strict_types=1);
namespace Proto\Media\Traits;

use Proto\Media\ImagePresets;
use Proto\Media\ImageProcessor;

/**
 * MediaImageOptimizationTrait
 *
 * Shared wiring for media services that store uploads on a Vault disk.
 * Between `$file->store(...)` and `$media->add()` call:
 *
 *   $this->optimizeUploadedImage($media, $file, 'posts');
 *
 * and in your delete path, after deleting the main file from Vault:
 *
 *   $this->deleteOptimizedImage($media, 'posts');
 *
 * The trait is a no-op for non-image media and degrades gracefully when
 * Imagick isn't available. Pass `$disk` when not using the `local` driver.
 *
 * Optional `$options['onRemoteWrite']` is forwarded to ImageProcessor for
 * CDN/object-header hooks on remote disks.
 *
 * @package Proto\Media\Traits
 */
trait MediaImageOptimizationTrait
{
	/**
	 * Re-encode the original and generate variants. Mutates `$media` in place.
	 *
	 * @param object $media In-progress media model (filename, path, type,
	 *   mimeType, fileSize, width, height, variants).
	 * @param object $file UploadFile instance.
	 * @param string $bucket Vault bucket name.
	 * @param string $disk Vault disk name.
	 * @param array<int, array<string, mixed>>|null $presets
	 * @param array<string, mixed>|null $options Processor options.
	 * @param callable|null $pathBuilder Optional `(string $bucket, string $filename): string`
	 *   used to refresh `$media->path` after rename.
	 * @return void
	 */
	protected function optimizeUploadedImage(
		object $media,
		object $file,
		string $bucket,
		string $disk = 'local',
		?array $presets = null,
		?array $options = null,
		?callable $pathBuilder = null
	): void
	{
		if (($media->type ?? null) !== 'image')
		{
			return;
		}
		if (!method_exists($file, 'isImageFile') || !$file->isImageFile())
		{
			return;
		}

		$filename = (string)($media->filename ?? '');
		if ($filename === '')
		{
			return;
		}

		$result = ImageProcessor::process(
			$disk,
			$bucket,
			$filename,
			$presets ?? ImagePresets::MEDIA,
			$options ?? ImagePresets::ORIGINAL_MEDIA
		);
		if ($result === null)
		{
			return;
		}

		$mainFile = $result['mainFile'] ?? $filename;
		$media->filename = $mainFile;
		if ($pathBuilder !== null)
		{
			$media->path = $pathBuilder($bucket, $mainFile);
		}
		if (!empty($result['mimeType']))
		{
			$media->mimeType = $result['mimeType'];
		}
		if (!empty($result['fileSize']))
		{
			$media->fileSize = (int)$result['fileSize'];
		}
		if (!empty($result['width']))
		{
			$media->width = (int)$result['width'];
		}
		if (!empty($result['height']))
		{
			$media->height = (int)$result['height'];
		}
		$media->variants = $result['variants'] ?? null;
	}

	/**
	 * Delete any variant files associated with the media record.
	 *
	 * @param object $media
	 * @param string $bucket
	 * @param string $disk
	 * @return void
	 */
	protected function deleteOptimizedImage(object $media, string $bucket, string $disk = 'local'): void
	{
		$variants = is_array($media->variants ?? null) ? $media->variants : null;
		ImageProcessor::deleteVariants($disk, $bucket, $variants);
	}
}
