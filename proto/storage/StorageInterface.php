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
     * This will add a new record to the storage.
     *
     * @return bool True on success, false on failure.
     */
    public function add(): bool;

    /**
     * This will update an existing record in the storage.
     *
     * @return bool True on success, false on failure.
     */
    public function update(): bool;

    /**
     * This will delete an existing record from the storage.
     *
     * @return bool True on success, false on failure.
     */
    public function delete(): bool;

    /**
     * This will retrieve an existing record from the storage.
     *
     * @param mixed $id The ID of the record to retrieve.
     * @return object|null The retrieved record or null if not found.
     */
    public function get(mixed $id): ?object;
}