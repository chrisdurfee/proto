<?php declare(strict_types=1);
namespace Proto\Api;

use Proto\Http\UploadFile;

/**
 * Class ImageValidator
 *
 * Provides functionality for validating image files including size and MIME type validation.
 *
 * @package Proto\Api
 */
class ImageValidator
{
	/**
	 * Default allowed MIME types for images.
	 *
	 * @var array
	 */
	protected static array $defaultMimeTypes = [
		'image/jpeg',
		'image/jpg',
		'image/png',
		'image/gif',
		'image/webp',
		'image/bmp',
		'image/tiff'
	];

	/**
	 * Default maximum file size in KB.
	 *
	 * @var int
	 */
	protected static int $defaultMaxSize = 2048; // 2MB

	/**
	 * Validates an image file.
	 *
	 * @param mixed $file The file to validate (UploadFile or file array)
	 * @param int|null $maxSizeKb Maximum size in KB
	 * @param array|null $allowedMimes Allowed MIME types
	 * @return array Returns ['valid' => bool, 'errors' => array]
	 */
	public static function validate(mixed $file, ?int $maxSizeKb = null, ?array $allowedMimes = null): array
	{
		$errors = [];
		$maxSizeKb = $maxSizeKb ?? static::$defaultMaxSize;
		$allowedMimes = $allowedMimes ?? static::$defaultMimeTypes;

		// Handle different file input types
		if ($file instanceof UploadFile)
		{
			$uploadFile = $file;
		}
		elseif (is_array($file) && isset($file['tmp_name']))
		{
			$uploadFile = new UploadFile($file);
		}
		else
		{
			return ['valid' => false, 'errors' => ['Invalid file format']];
		}

		// Check if file exists and was uploaded successfully
		if (!static::isValidUpload($uploadFile))
		{
			return ['valid' => false, 'errors' => ['File upload failed or file does not exist']];
		}

		// Validate file size
		if (!static::validateSize($uploadFile, $maxSizeKb))
		{
			$errors[] = "File size exceeds maximum allowed size of {$maxSizeKb}KB";
		}

		// Validate MIME type
		if (!static::validateMimeType($uploadFile, $allowedMimes))
		{
			$allowedTypes = implode(', ', static::getMimeExtensions($allowedMimes));
			$errors[] = "File type not allowed. Allowed types: {$allowedTypes}";
		}

		// Validate if it's actually an image by checking file contents
		if (!static::validateImageContent($uploadFile))
		{
			$errors[] = "File is not a valid image";
		}

		return [
			'valid' => empty($errors),
			'errors' => $errors
		];
	}

	/**
	 * Checks if the upload is valid.
	 *
	 * @param UploadFile $file
	 * @return bool
	 */
	protected static function isValidUpload(UploadFile $file): bool
	{
		$filePath = $file->getFilePath();
		return file_exists($filePath) && is_readable($filePath) && $file->getSize() > 0;
	}

	/**
	 * Validates file size.
	 *
	 * @param UploadFile $file
	 * @param int $maxSizeKb Maximum size in KB
	 * @return bool
	 */
	protected static function validateSize(UploadFile $file, int $maxSizeKb): bool
	{
		$fileSizeKb = $file->getSize() / 1024;
		return $fileSizeKb <= $maxSizeKb;
	}

	/**
	 * Validates MIME type.
	 *
	 * @param UploadFile $file
	 * @param array $allowedMimes
	 * @return bool
	 */
	protected static function validateMimeType(UploadFile $file, array $allowedMimes): bool
	{
		$fileMime = $file->getType();

		// Also check actual MIME type using finfo for security
		$actualMime = static::getActualMimeType($file->getFilePath());

		return in_array($fileMime, $allowedMimes) && in_array($actualMime, $allowedMimes);
	}

	/**
	 * Gets the actual MIME type of a file using finfo.
	 *
	 * @param string $filePath
	 * @return string
	 */
	protected static function getActualMimeType(string $filePath): string
	{
		if (function_exists('finfo_open'))
		{
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mimeType = finfo_file($finfo, $filePath);
			finfo_close($finfo);
			return $mimeType ?: '';
		}

		return '';
	}

	/**
	 * Validates image content by attempting to get image info.
	 *
	 * @param UploadFile $file
	 * @return bool
	 */
	protected static function validateImageContent(UploadFile $file): bool
	{
		$imageInfo = @getimagesize($file->getFilePath());
		return $imageInfo !== false && isset($imageInfo[0]) && isset($imageInfo[1]);
	}

	/**
	 * Converts MIME types to file extensions for error messages.
	 *
	 * @param array $mimeTypes
	 * @return array
	 */
	protected static function getMimeExtensions(array $mimeTypes): array
	{
		$extensions = [];
		foreach ($mimeTypes as $mime)
		{
			switch ($mime)
			{
				case 'image/jpeg':
					$extensions[] = 'jpeg';
					break;
				case 'image/jpg':
					$extensions[] = 'jpg';
					break;
				case 'image/png':
					$extensions[] = 'png';
					break;
				case 'image/gif':
					$extensions[] = 'gif';
					break;
				case 'image/webp':
					$extensions[] = 'webp';
					break;
				case 'image/bmp':
					$extensions[] = 'bmp';
					break;
				case 'image/tiff':
					$extensions[] = 'tiff';
					break;
				default:
					$extensions[] = str_replace('image/', '', $mime);
			}
		}
		return array_unique($extensions);
	}

	/**
	 * Parses MIME types from a string (e.g., "jpeg,jpg,png,gif").
	 *
	 * @param string $mimeString
	 * @return array
	 */
	public static function parseMimeTypes(string $mimeString): array
	{
		$types = explode(',', $mimeString);
		$mimeTypes = [];

		foreach ($types as $type)
		{
			$type = trim($type);
			if (!str_starts_with($type, 'image/'))
			{
				$type = 'image/' . $type;
			}
			$mimeTypes[] = $type;
		}

		return $mimeTypes;
	}
}