<?php declare(strict_types=1);
namespace Proto\Jobs\Examples;

use Proto\Jobs\Job;

/**
 * ProcessImageJob
 *
 * Example job for processing images (resize, optimize, etc.).
 *
 * @package Proto\Jobs\Examples
 */
class ProcessImageJob extends Job
{
	/**
	 * @var string $queue The queue name for this job
	 */
	protected string $queue = 'images';

	/**
	 * @var int $timeout Job timeout in seconds
	 */
	protected int $timeout = 300;

	/**
	 * Execute the job.
	 *
	 * @param mixed $data The job data
	 * @return mixed The result of the job execution
	 */
	public function handle(mixed $data): mixed
	{
		// Validate required data
		if (!isset($data['source_path'])) {
			throw new \InvalidArgumentException('Image processing job requires source_path');
		}

		$sourcePath = $data['source_path'];
		$operations = $data['operations'] ?? ['resize'];
		$outputPath = $data['output_path'] ?? null;

		// Validate source file exists
		if (!file_exists($sourcePath)) {
			throw new \RuntimeException("Source image not found: {$sourcePath}");
		}

		error_log("Processing image: {$sourcePath}");

		$results = [];

		try {
			foreach ($operations as $operation) {
				$result = $this->performOperation($operation, $sourcePath, $data);
				$results[$operation] = $result;
			}

			error_log("Image processing completed for: {$sourcePath}");
			return [
				'status' => 'completed',
				'source_path' => $sourcePath,
				'results' => $results,
				'processed_at' => date('Y-m-d H:i:s')
			];

		} catch (\Exception $e) {
			error_log("Failed to process image {$sourcePath}: " . $e->getMessage());
			throw $e;
		}
	}

	/**
	 * Perform a specific image operation.
	 *
	 * @param string $operation The operation to perform
	 * @param string $sourcePath Source image path
	 * @param array $data Job data
	 * @return array Operation result
	 */
	protected function performOperation(string $operation, string $sourcePath, array $data): array
	{
		switch ($operation) {
			case 'resize':
				return $this->resizeImage($sourcePath, $data);

			case 'thumbnail':
				return $this->createThumbnail($sourcePath, $data);

			case 'optimize':
				return $this->optimizeImage($sourcePath, $data);

			case 'watermark':
				return $this->addWatermark($sourcePath, $data);

			default:
				throw new \InvalidArgumentException("Unknown operation: {$operation}");
		}
	}

	/**
	 * Resize an image.
	 *
	 * @param string $sourcePath Source image path
	 * @param array $data Job data
	 * @return array Resize result
	 */
	protected function resizeImage(string $sourcePath, array $data): array
	{
		$width = $data['width'] ?? 800;
		$height = $data['height'] ?? 600;
		$outputPath = $data['output_path'] ?? $this->generateOutputPath($sourcePath, 'resized');

		// Simulate processing time
		sleep(2);

		// In a real implementation, you'd use GD, ImageMagick, or similar
		error_log("Resizing image to {$width}x{$height}");

		return [
			'operation' => 'resize',
			'output_path' => $outputPath,
			'width' => $width,
			'height' => $height,
			'file_size' => rand(50000, 200000) // Simulated file size
		];
	}

	/**
	 * Create a thumbnail.
	 *
	 * @param string $sourcePath Source image path
	 * @param array $data Job data
	 * @return array Thumbnail result
	 */
	protected function createThumbnail(string $sourcePath, array $data): array
	{
		$size = $data['thumbnail_size'] ?? 150;
		$outputPath = $this->generateOutputPath($sourcePath, 'thumb');

		// Simulate processing time
		sleep(1);

		error_log("Creating thumbnail of size {$size}x{$size}");

		return [
			'operation' => 'thumbnail',
			'output_path' => $outputPath,
			'size' => $size,
			'file_size' => rand(5000, 15000) // Simulated file size
		];
	}

	/**
	 * Optimize an image.
	 *
	 * @param string $sourcePath Source image path
	 * @param array $data Job data
	 * @return array Optimization result
	 */
	protected function optimizeImage(string $sourcePath, array $data): array
	{
		$quality = $data['quality'] ?? 85;
		$outputPath = $data['output_path'] ?? $sourcePath; // Optimize in place by default

		// Simulate processing time
		sleep(1);

		error_log("Optimizing image with quality {$quality}%");

		return [
			'operation' => 'optimize',
			'output_path' => $outputPath,
			'quality' => $quality,
			'original_size' => rand(500000, 2000000), // Simulated
			'optimized_size' => rand(200000, 800000), // Simulated
			'compression_ratio' => rand(40, 70) . '%'
		];
	}

	/**
	 * Add watermark to an image.
	 *
	 * @param string $sourcePath Source image path
	 * @param array $data Job data
	 * @return array Watermark result
	 */
	protected function addWatermark(string $sourcePath, array $data): array
	{
		$watermarkPath = $data['watermark_path'] ?? '/path/to/default/watermark.png';
		$position = $data['watermark_position'] ?? 'bottom-right';
		$outputPath = $this->generateOutputPath($sourcePath, 'watermarked');

		// Simulate processing time
		sleep(1);

		error_log("Adding watermark at position: {$position}");

		return [
			'operation' => 'watermark',
			'output_path' => $outputPath,
			'watermark_path' => $watermarkPath,
			'position' => $position
		];
	}

	/**
	 * Generate output path for processed images.
	 *
	 * @param string $sourcePath Source image path
	 * @param string $suffix File suffix
	 * @return string Generated output path
	 */
	protected function generateOutputPath(string $sourcePath, string $suffix): string
	{
		$pathInfo = pathinfo($sourcePath);
		$directory = $pathInfo['dirname'];
		$filename = $pathInfo['filename'];
		$extension = $pathInfo['extension'];

		return "{$directory}/{$filename}_{$suffix}.{$extension}";
	}

	/**
	 * Handle job failure.
	 *
	 * @param \Throwable $exception
	 * @param mixed $data
	 * @return void
	 */
	public function failed(\Throwable $exception, mixed $data): void
	{
		$sourcePath = $data['source_path'] ?? 'unknown';
		error_log("Image processing job failed for {$sourcePath}: " . $exception->getMessage());

		// Clean up any partial files, notify administrators, etc.
	}
}
