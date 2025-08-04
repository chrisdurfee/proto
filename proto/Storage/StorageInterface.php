<?php declare(strict_types=1);
namespace Proto\Storage;

/**
 * StorageInterface
 *
 * This will create a storage interface that will
 * define the necessary methods for CRUD operations.
 *
 * @package Proto\Storage
 */
interface StorageInterface
{
    /**
	 * Normalize data from snake_case to camelCase.
	 *
	 * @param mixed $data Raw data.
	 * @return mixed
	 */
	public function normalize(mixed $data): mixed;
}