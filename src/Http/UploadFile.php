<?php declare(strict_types=1);
namespace Proto\Http;

use Proto\Utils\Files\Vault;
use Proto\Utils\Filter\Sanitize;
use Proto\Utils\Files\File;

/**
 * UploadFile
 *
 * Handles uploaded files, ensuring safe storage, retrieval, and manipulation.
 *
 * @package Proto\Http
 */
class UploadFile
{
	/**
	 * Holds the newly generated unique file name.
	 *
	 * @var string
	 */
	protected string $newFileName;

	/**
	 * System temporary directory.
	 *
	 * @var string
	 */
	protected string $tmpDir;

	/**
	 * Cached MIME type.
	 *
	 * @var string|null
	 */
	protected ?string $mime = null;

	/**
	 * Initializes an uploaded file instance.
	 *
	 * @param array $tmpFile PHP file upload array.
	 */
	public function __construct(protected array $tmpFile)
	{
		$this->tmpDir = sys_get_temp_dir();
		$this->newFileName = File::createNewName($this->getOriginalName());
		$this->renameTmpFile();
	}

	/**
	 * Retrieves the full path of the temporary file.
	 *
	 * @return string
	 */
	public function getFilePath(): string
	{
		return "{$this->tmpDir}/{$this->newFileName}";
	}

	/**
	 * Alias for getFilePath to support drivers expecting getPath().
	 *
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->getFilePath();
	}

	/**
	 * Renames the uploaded temporary file to ensure a unique name.
	 *
	 * @return void
	 */
	protected function renameTmpFile(): void
	{
		File::rename($this->getTmpName(), $this->getFilePath());
	}

	/**
	 * Retrieves a value from the uploaded file array.
	 *
	 * @param string $key File attribute key.
	 * @return string|null Sanitized value or null if not found.
	 */
	protected function getValue(string $key): ?string
	{
		return isset($this->tmpFile[$key]) ? Sanitize::string((string)$this->tmpFile[$key]) : null;
	}

	/**
	 * Retrieves the original file name.
	 *
	 * @return string
	 */
	public function getOriginalName(): string
	{
		return $this->getValue('name') ?? '';
	}

	/**
	 * Retrieves the new unique file name.
	 *
	 * @return string
	 */
	public function getNewName(): string
	{
		return $this->newFileName;
	}

	/**
	 * Retrieves the file type.
	 *
	 * @return string
	 */
	public function getType(): string
	{
		return $this->getValue('type') ?? '';
	}

	/**
	 * Retrieves the file size.
	 *
	 * @return int File size in bytes.
	 */
	public function getSize(): int
	{
		return (int) ($this->getValue('size') ?? 0);
	}

	/**
	 * Retrieves the temporary file path.
	 *
	 * @return string
	 */
	public function getTmpName(): string
	{
		return $this->getValue('tmp_name') ?? '';
	}

	/**
	 * Retrieves the MIME type of the uploaded file.
	 *
	 * @return string
	 */
	public function getMimeType(): string
	{
		if ($this->mime !== null)
		{
			return $this->mime;
		}

		$path = $this->getFilePath();
		if (!is_file($path))
		{
			return $this->mime = '';
		}

		$finfo = new \finfo(FILEINFO_MIME_TYPE);
		$mime = $finfo->file($path) ?: '';

		// Normalize a few common misdetections
		if ($mime === 'text/plain' || $mime === 'application/octet-stream')
		{
			$ext = strtolower(pathinfo($this->getOriginalName(), PATHINFO_EXTENSION));
			$map = [
				'svg' => 'image/svg+xml',
				'webp' => 'image/webp',
				'heic' => 'image/heic',
				'heif' => 'image/heif',
			];
			if (isset($map[$ext]))
			{
				$mime = $map[$ext];
			}
		}

		return $this->mime = $mime;
	}

	/**
	 * Moves the file to the specified path.
	 *
	 * @param string $destination The destination path.
	 * @return bool True if successful, false otherwise.
	 */
	public function move(string $destination): bool
	{
		return File::move($this->getFilePath(), $destination);
	}

	/**
	 * Stores the file in a specified storage driver.
	 *
	 * @param string $driver The storage driver (e.g., local, S3).
	 * @param string|null $bucket Optional storage bucket.
	 * @return bool True if successfully stored, false otherwise.
	 */
	public function store(string $driver, ?string $bucket = null): bool
	{
		return Vault::disk($driver, $bucket)->store($this);
	}
}