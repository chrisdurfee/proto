<?php declare(strict_types=1);
namespace Proto\Controllers\Traits;

use Proto\Http\Router\Request;

/**
 * FileUploadTrait
 *
 * Provides convenient helpers for single and batch file uploads with
 * validation, storage, and metadata extraction.
 *
 * Used by ResourceController but can be applied to any controller
 * that needs file upload handling.
 *
 * @package Proto\Controllers\Traits
 */
trait FileUploadTrait
{
	/**
	 * Validate and store an uploaded file, returning the new filename.
	 *
	 * @param Request $request The request object.
	 * @param string $fieldName The form field name for the file input.
	 * @param string $disk The storage disk (e.g., 'local', 's3').
	 * @param string $directory The subdirectory within the disk.
	 * @param string $rules Validation rules (e.g., 'image:2048|mimes:jpeg,png').
	 * @return string|null New filename, or null if no file uploaded.
	 */
	protected function handleFileUpload(
		Request $request,
		string $fieldName,
		string $disk = 'local',
		string $directory = '',
		string $rules = 'image:2048'
	): ?string
	{
		$file = $request->file($fieldName);
		if (!$file)
		{
			return null;
		}

		$this->validateRules([$fieldName => $file], [$fieldName => $rules]);
		$file->store($disk, $directory);

		return $file->getNewName();
	}

	/**
	 * Validate, store, and return metadata for multiple file uploads.
	 *
	 * @param Request $request The request object.
	 * @param string $fieldName The form field name for the file array input.
	 * @param string $disk The storage disk (e.g., 'local', 's3').
	 * @param string $directory The subdirectory within the disk.
	 * @param string $rules Validation rules (e.g., 'image:2048|mimes:jpeg,png').
	 * @return array Array of file metadata objects, empty if no files.
	 */
	protected function handleMediaUpload(
		Request $request,
		string $fieldName,
		string $disk = 'local',
		string $directory = '',
		string $rules = 'image:2048'
	): array
	{
		$files = $request->fileArray($fieldName);
		if (empty($files))
		{
			return [];
		}

		$mediaItems = [];
		foreach ($files as $file)
		{
			$this->validateRules([$fieldName => $file], [$fieldName => $rules]);
			$file->store($disk, $directory);
			$mediaItems[] = (object)[
				'fileName' => $file->getNewName(),
				'originalName' => $file->getOriginalName(),
				'mimeType' => $file->getMimeType(),
				'size' => $file->getSize()
			];
		}

		return $mediaItems;
	}
}
