<?php declare(strict_types=1);
namespace Proto\Models;

/**
 * StorageWrapper
 *
 * This will wrap the storage object.
 *
 * @package Proto\Models
 */
class StorageWrapper
{
    /**
     * This will set the storage object.
     *
     * @param object $storage
     * @return void
     */
    public function __construct(
        protected object $storage
    )
    {
    }

    /**
     * This will check to call the method and normalizew the result.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments): mixed
    {
        if (!$this->isCallable($this->storage, $method))
        {
            return null;
        }

        $result = \call_user_func_array([$this->storage, $method], $arguments);
        return $this->storage->normalize($result);
    }

    /**
     * This will check if a method is callable.
     *
     * @param object $object
     * @param string $method
     * @return bool
     */
    protected function isCallable(object $object, string $method): bool
    {
        return \is_callable([$object, $method]);
    }
}
