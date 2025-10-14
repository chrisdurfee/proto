<?php declare(strict_types=1);
namespace Proto\Api;

use Proto\Http\UploadFile;

/**
 * Class FileValidator
 *
 * Provides functionality for validating generic file uploads including size and MIME type validation.
 *
 * @package Proto\Api
 */
class FileValidator
{
	/**
	 * Default allowed MIME types for common file types.
	 *
	 * @var array
	 */
	protected static array $defaultMimeTypes = [
		// Documents
		'application/pdf',
		'application/msword',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/vnd.ms-excel',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'application/vnd.ms-powerpoint',
		'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'text/plain',
		'text/csv',
		// Images
		'image/jpeg',
		'image/jpg',
		'image/png',
		'image/gif',
		'image/webp',
		'image/bmp',
		// Archives
		'application/zip',
		'application/x-rar-compressed',
		'application/x-7z-compressed',
		'application/x-tar',
		'application/gzip',
		// Audio
		'audio/mpeg',
		'audio/wav',
		'audio/ogg',
		// Video
		'video/mp4',
		'video/mpeg',
		'video/quicktime',
		'video/x-msvideo'
	];

	/**
	 * Default maximum file size in KB.
	 *
	 * @var int
	 */
	protected static int $defaultMaxSize = 10240; // 10MB

	/**
	 * Validates a file upload.
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

		// Validate MIME type if specific types are allowed
		if (!empty($allowedMimes) && !static::validateMimeType($uploadFile, $allowedMimes))
		{
			$allowedExtensions = static::getExtensionsFromMimes($allowedMimes);
			$errors[] = "File type not allowed. Allowed types: " . implode(', ', $allowedExtensions);
		}

		// Validate file content matches declared MIME type
		if (!static::validateFileContent($uploadFile, $allowedMimes))
		{
			$errors[] = "File content does not match its declared type";
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

		// Check if either the declared or actual MIME type is in the allowed list
		return in_array($fileMime, $allowedMimes) || in_array($actualMime, $allowedMimes);
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
	 * Validates file content by checking if actual MIME matches allowed types.
	 *
	 * @param UploadFile $file
	 * @param array $allowedMimes
	 * @return bool
	 */
	protected static function validateFileContent(UploadFile $file, array $allowedMimes): bool
	{
		// If no specific MIME types are enforced, skip content validation
		if (empty($allowedMimes) || $allowedMimes === static::$defaultMimeTypes)
		{
			return true;
		}

		$actualMime = static::getActualMimeType($file->getFilePath());

		// If we can't determine actual MIME, allow it (fail open for compatibility)
		if (empty($actualMime))
		{
			return true;
		}

		return in_array($actualMime, $allowedMimes);
	}

	/**
	 * Converts MIME types to file extensions for error messages.
	 *
	 * @param array $mimeTypes
	 * @return array
	 */
	protected static function getExtensionsFromMimes(array $mimeTypes): array
	{
		$mimeToExtension = [
			// Documents
			'application/pdf' => 'pdf',
			'application/msword' => 'doc',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
			'application/vnd.ms-excel' => 'xls',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
			'application/vnd.ms-powerpoint' => 'ppt',
			'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
			'text/plain' => 'txt',
			'text/csv' => 'csv',
			// Images
			'image/jpeg' => 'jpeg',
			'image/jpg' => 'jpg',
			'image/png' => 'png',
			'image/gif' => 'gif',
			'image/webp' => 'webp',
			'image/bmp' => 'bmp',
			'image/tiff' => 'tiff',
			// Archives
			'application/zip' => 'zip',
			'application/x-rar-compressed' => 'rar',
			'application/x-7z-compressed' => '7z',
			'application/x-tar' => 'tar',
			'application/gzip' => 'gz',
			// Audio
			'audio/mpeg' => 'mp3',
			'audio/wav' => 'wav',
			'audio/ogg' => 'ogg',
			// Video
			'video/mp4' => 'mp4',
			'video/mpeg' => 'mpeg',
			'video/quicktime' => 'mov',
			'video/x-msvideo' => 'avi'
		];

		$extensions = [];
		foreach ($mimeTypes as $mime)
		{
			if (isset($mimeToExtension[$mime]))
			{
				$extensions[] = $mimeToExtension[$mime];
			}
			else
			{
				// Fallback to extracting from MIME type
				$parts = explode('/', $mime);
				$extensions[] = end($parts);
			}
		}

		return array_unique($extensions);
	}

	/**
	 * Parses MIME types from a string (e.g., "pdf,doc,docx").
	 *
	 * @param string $mimeString
	 * @return array
	 */
	public static function parseMimeTypes(string $mimeString): array
	{
		$extensionToMime = [
			// Documents
			'pdf' => 'application/pdf',
			'doc' => 'application/msword',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'xls' => 'application/vnd.ms-excel',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'ppt' => 'application/vnd.ms-powerpoint',
			'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'txt' => 'text/plain',
			'csv' => 'text/csv',
			// Images
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpg',
			'png' => 'image/png',
			'gif' => 'image/gif',
			'webp' => 'image/webp',
			'bmp' => 'image/bmp',
			'tiff' => 'image/tiff',
			// Archives
			'zip' => 'application/zip',
			'rar' => 'application/x-rar-compressed',
			'7z' => 'application/x-7z-compressed',
			'tar' => 'application/x-tar',
			'gz' => 'application/gzip',
			// Audio
			'mp3' => 'audio/mpeg',
			'wav' => 'audio/wav',
			'ogg' => 'audio/ogg',
			// Video
			'mp4' => 'video/mp4',
			'mpeg' => 'video/mpeg',
			'mov' => 'video/quicktime',
			'avi' => 'video/x-msvideo'
		];

		$types = explode(',', $mimeString);
		$mimeTypes = [];

		foreach ($types as $type)
		{
			$type = trim(strtolower($type));

			// Check if it's already a MIME type
			if (str_contains($type, '/'))
			{
				$mimeTypes[] = $type;
			}
			// Convert extension to MIME type
			elseif (isset($extensionToMime[$type]))
			{
				$mimeTypes[] = $extensionToMime[$type];
			}
		}

		return array_unique($mimeTypes);
	}
}
