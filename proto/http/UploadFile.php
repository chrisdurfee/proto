<?php declare(strict_types=1);
namespace Proto\Http;

use Proto\Utils\Files\Vault;
use Proto\Utils\Filter\Sanitize;
use Proto\Utils\Files\File;

/**
 * UploadFile
 *
 * This will handle the upload file.
 *
 * @package Proto\Http
 */
class UploadFile
{
	/**
	 * @var string $newFileName
	 */
	protected string $newFileName;

	/**
	 * @var string $PATH
	 */
	protected const PATH = '/tmp/';

	/**
	 * This will set up the upload file.
	 *
	 * @param array $tmpFile
	 * @return void
	 */
	public function __construct(
		protected array $tmpFile
	)
	{
		$this->createNewName();
		$this->setNewName();
	}

	/**
	 * This will get the file path.
	 *
	 * @return string
	 */
	public function getFilePath(): string
	{
		$tmp = sys_get_temp_dir(); // gets the system temp dir.
		return $tmp . "/" . $this->newFileName;
	}

	/**
	 * This will set the new name.
	 *
	 * @return void
	 */
	protected function setNewName(): void
	{
		File::rename($this->getTmpName(), $this->getFilePath());
	}

	/**
	 * This will get a file value.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get(string $key): mixed
	{
		$value = $this->tmpFile[$key] ?? null;
		if (isset($value) === false)
		{
			return $value;
		}

		return Sanitize::string($value);
	}

	/**
	 * This will get the value from the tmp file.
	 *
	 * @param string $key
	 * @return string
	 */
	protected function getValue(string $key): string
	{
		$value = $this->tmpFile[$key] ?? '';
		return Sanitize::string($value);
	}

	/**
	 * This will get the original file name.
	 *
	 * @return string
	 */
	public function getOriginalName(): string
	{
		return $this->getValue('name');
	}

	/**
	 * This will get the new name name.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->getNewName();
	}

	/**
	 * This will get the new unique file name.
	 *
	 * @return string
	 */
	public function getNewName(): string
	{
		return $this->newFileName;
	}

	/**
	 * This will get the type.
	 *
	 * @return string
	 */
	public function getType(): string
	{
		return $this->getValue('type');
	}

	/**
	 * This will get the size.
	 *
	 * @return string
	 */
	public function getSize(): string
	{
		return $this->getValue('size');
	}

	/**
	 * This will get the tmp name.
	 *
	 * @return string
	 */
	public function getTmpName(): string
	{
		return $this->tmpFile['tmp_name'] ?? '';
	}

	/**
	 * This will move the file.
	 *
	 * @param string $path
	 * @return bool
	 */
	public function move(string $path): bool
	{
		return File::move($this->getTmpName(), $path);
	}

	/**
	 * This will create a unique new file name to stop
	 * upload conflicts.
	 *
	 * @return string
	 */
	protected function createNewName(): string
	{
		$fileName = $this->getOriginalName();
		return ($this->newFileName = File::createNewName($fileName));
	}

	/**
	 * This will store the file.
	 *
	 * @param string $driver
	 * @param string|null $bucket
	 * @return bool
	 */
	public function store(string $driver, ?string $bucket = null): bool
	{
		return Vault::disk($driver, $bucket)->store($this);
	}
}
