<?php declare(strict_types=1);
namespace Proto\Models;

/**
 * StorageWrapper
 *
 * This class wraps the storage object and provides methods to interact with it.
 *
 * @package Proto\Models
 */
class StorageWrapper
{
	/**
	 * The storage object.
	 *
	 * @var object
	 */
	protected object $storage;

	/**
	 * Sets the storage object.
	 *
	 * @param object $storage The storage object.
	 * @return void
	 */
	public function __construct(object $storage)
	{
		$this->storage = $storage;
	}

	/**
	 * Calls the method on the storage object and normalizes the result.
	 *
	 * @param string $method The method name.
	 * @param array $arguments The arguments to pass to the method.
	 * @return mixed The normalized result.
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
	 * Checks if a method is callable on the storage object.
	 *
	 * @param object $object The storage object.
	 * @param string $method The method name.
	 * @return bool True if the method is callable, false otherwise.
	 */
	protected function isCallable(object $object, string $method): bool
	{
		return \is_callable([$object, $method]);
	}
}
