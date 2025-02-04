<?php declare(strict_types=1);
namespace Proto\Cache\Drivers;

/**
 * Driver
 *
 * This will be the base class for all cache drivers.
 *
 * @package Proto\Cache\Drivers
 * @abstract
 */
abstract class Driver
{
    /**
     * @var array $errors
     */
    protected array $errors = [];

    /**
     * This will set an error.
     *
     * @param \Exception $error
     * @return void
     */
    protected function setError(\Exception $error): void
    {
        array_push($this->errors, $error);
    }

    /**
     * This will get the last error.
     *
     * @return \Exception|null
     */
    public function getLastError(): ?\Exception
    {
        return $this->errors[count($this->errors) - 1] ?? null;
    }

    /**
     * This will check if the cache is ready to use.
     *
     * @return bool
     */
    abstract public function isSupported(): bool;

    /**
     * This will get a value from the cache.
     *
     * @param string $key
     * @return string|null
     */
	abstract public function get(string $key): ?string;

    /**
     * This will check if the item is in the cache.
     *
     * @param string $key
     * @return bool
     */
    abstract public function has(string $key): bool;

    /**
     * This will increment an item in the cache.
     *
     * @param string $key
     * @return int
     */
    abstract public function incr(string $key): int;

    /**
     * This will check if the item is in the cache.
     *
     * @param string $key
     * @return array|null
     */
    abstract public function keys(string $key): ?array;

    /**
     * This will delete a value from the cache.
     *
     * @param string $key
     * @return bool
     */
    abstract public function delete(string $key): bool;

    /**
     * This will set a value to the cache.
     *
     * @param string $key
     * @param string $value
     * @param int|null $expire
     * @return void
     */
    abstract public function set(string $key, string $value, ?int $expire = null): void;
}