<?php declare(strict_types=1);
namespace Proto\Media;

/**
 * ImagePresets
 *
 * Default named preset bundles for {@see ImageProcessor::process()}.
 * Apps may ignore these and pass their own arrays, or redefine constants
 * in an app-level presets class (recommended for product-specific sizes).
 *
 * @package Proto\Media
 */
class ImagePresets
{
	/**
	 * Square avatar variants (lists, comments, headers).
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public const AVATAR = [
		['name' => 'thumb', 'mode' => 'square', 'width' => 128, 'quality' => 82],
		['name' => 'card', 'mode' => 'fit', 'width' => 400, 'height' => 400, 'quality' => 82],
	];

	/**
	 * Wide cover-image variants.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public const COVER = [
		['name' => 'thumb', 'mode' => 'width', 'width' => 560, 'quality' => 80],
		['name' => 'card', 'mode' => 'width', 'width' => 1600, 'quality' => 82],
	];

	/**
	 * Generic content media (posts, galleries, entity photos).
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public const MEDIA = [
		['name' => 'thumb', 'mode' => 'fit', 'width' => 320, 'height' => 320, 'quality' => 80],
		['name' => 'card', 'mode' => 'width', 'width' => 800, 'quality' => 82],
		['name' => 'large', 'mode' => 'width', 'width' => 1600, 'quality' => 84],
	];

	/**
	 * Default options for re-encoding originals on profile/cover uploads.
	 *
	 * @var array<string, mixed>
	 */
	public const ORIGINAL_PROFILE = [
		'reencodeOriginal' => true,
		'originalMaxDim' => 2048,
		'originalQuality' => 85,
		'variantFormat' => 'auto',
	];

	/**
	 * Default options for re-encoding originals on content media uploads.
	 *
	 * @var array<string, mixed>
	 */
	public const ORIGINAL_MEDIA = [
		'reencodeOriginal' => true,
		'originalMaxDim' => 2560,
		'originalQuality' => 85,
		'variantFormat' => 'auto',
	];
}
