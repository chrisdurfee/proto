<?php declare(strict_types=1);
namespace Proto\Controllers\Traits;

use Proto\Http\Router\Request;
use Proto\Media\ImagePresets;
use Proto\Media\ImageProcessor;

/**
 * ImageOptimizationTrait
 *
 * Controller-level wrapper around {@see ImageProcessor::process()} for
 * entities that store a single image (avatar/cover/logo/banner) directly on
 * the parent row. Pairs with {@see FileUploadTrait::handleFileUpload()}.
 *
 * Typical usage in `modifyAddItem` / `modifyUpdateItem`:
 *
 *   $upload = $this->handleOptimizedImageUpload(
 *       $request, 'coverImage', 'vehicles', ImagePresets::COVER
 *   );
 *   if ($upload !== null)
 *   {
 *       $data->coverImage = $upload['mainFile'];
 *       $data->coverImageVariants = $upload['variants'];
 *   }
 *
 * Apps that remap the storage driver (e.g. local → s3 in production) should
 * pass the resolved disk explicitly, or wrap this trait to inject defaults.
 *
 * @package Proto\Controllers\Traits
 */
trait ImageOptimizationTrait
{
	/**
	 * Validate + store an uploaded image, then run it through
	 * {@see ImageProcessor::process()} so derivative variants are generated.
	 *
	 * Returns null when no file was provided. When Imagick is unavailable
	 * or the processor fails, the original (stored) filename is returned
	 * with an empty variants map so callers can still save the row.
	 *
	 * @param Request $request
	 * @param string $fieldName Form field name (e.g. 'coverImage').
	 * @param string $bucket Vault bucket / directory (e.g. 'vehicles').
	 * @param array<int, array<string, mixed>> $presets Variant preset list.
	 * @param string $rules Validation rules for {@see handleFileUpload()}.
	 * @param array<string, mixed>|null $originalOptions Override options for
	 *   re-encoding the original (defaults to {@see ImagePresets::ORIGINAL_PROFILE}).
	 * @param string $disk Vault disk name.
	 * @return array{mainFile:string, variants: array<string,string>|null}|null
	 */
	protected function handleOptimizedImageUpload(
		Request $request,
		string $fieldName,
		string $bucket,
		array $presets,
		string $rules = 'image:10240',
		?array $originalOptions = null,
		string $disk = 'local'
	): ?array
	{
		$filename = $this->handleFileUpload($request, $fieldName, $disk, $bucket, $rules);
		if (!$filename)
		{
			return null;
		}

		$result = ImageProcessor::process(
			$disk,
			$bucket,
			$filename,
			$presets,
			$originalOptions ?? ImagePresets::ORIGINAL_PROFILE
		);

		if ($result === null)
		{
			return [
				'mainFile' => $filename,
				'variants' => null,
			];
		}

		return [
			'mainFile' => (string)($result['mainFile'] ?? $filename),
			'variants' => $result['variants'] ?? null,
		];
	}
}
