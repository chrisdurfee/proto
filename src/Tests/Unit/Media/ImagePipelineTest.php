<?php declare(strict_types=1);
namespace Proto\Tests\Unit\Media;

use Proto\Media\DiskScratch;
use Proto\Media\ImagePresets;
use Proto\Media\ImageProcessor;
use PHPUnit\Framework\TestCase;

/**
 * ImagePipelineTest
 *
 * @package Proto\Tests\Unit\Media
 */
class ImagePipelineTest extends TestCase
{
	/**
	 * @return void
	 */
	public function testIsSupportedReflectsImagick(): void
	{
		$this->assertSame(extension_loaded('imagick'), ImageProcessor::isSupported());
	}

	/**
	 * @return void
	 */
	public function testPresetsAreNonEmpty(): void
	{
		$this->assertNotEmpty(ImagePresets::AVATAR);
		$this->assertNotEmpty(ImagePresets::COVER);
		$this->assertNotEmpty(ImagePresets::MEDIA);
		$this->assertArrayHasKey('reencodeOriginal', ImagePresets::ORIGINAL_PROFILE);
	}

	/**
	 * @return void
	 */
	public function testScratchPathPreservesBasename(): void
	{
		$path = DiskScratch::scratchPath('photo.webp', 'img');
		$this->assertStringEndsWith('/photo.webp', $path);
		$this->assertDirectoryExists(dirname($path));
		DiskScratch::cleanup([$path]);
	}
}
