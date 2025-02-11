<?php declare(strict_types=1);
namespace Proto\Storage;

use Proto\Events\EventProxy;
use Proto\Events\Events;
use Proto\Models\ModelInterface;

/**
 * StorageProxy
 *
 * This class creates a storage proxy object that dispatches events for all actions the storage layer is calling.
 * This allows developers to track when the storage is being modified without manually dispatching events.
 *
 * @package Proto\Storage
 */
class StorageProxy extends EventProxy
{
	/**
	 * The model associated with the storage proxy.
	 *
	 * @var ModelInterface
	 */
	protected ModelInterface $model;

	/**
	 * Sets up the storage proxy.
	 *
	 * @param ModelInterface $model The model associated with the storage proxy.
	 * @param StorageInterface $storage The storage object.
	 * @return void
	 */
	public function __construct(ModelInterface &$model, StorageInterface &$storage)
	{
		$this->model = $model;
		$target = $this->getModelName($model);
		parent::__construct($target, $storage);
	}

	/**
	 * Retrieves the model name.
	 *
	 * @param ModelInterface $model The model object.
	 * @return string The model name.
	 */
	protected function getModelName(ModelInterface $model): string
	{
		$reflect = new \ReflectionClass($model);
		return $reflect->getShortName();
	}

	/**
	 * Retrieves the event payload.
	 *
	 * @param array $args The arguments passed to the method.
	 * @param mixed $result The result of the method call.
	 * @return object The event payload.
	 */
	protected function getResponse(array $args, mixed $result = false): object
	{
		$data = null;
		if (!is_object($result) && !is_array($result))
		{
			$data = $this->model->getData();
		}
		else
		{
			$response = $result->rows ?? $result->row ?? $result;
			if ($result)
			{
				$items = is_array($response) ? $response : [$response];
				$data = $this->model->convertRows($items);
			}
		}

		return (object)[
			'args' => $args,
			'data' => $data
		];
	}

	/**
	 * Publishes the event.
	 *
	 * @param string $method The method name.
	 * @param mixed $payload The event payload.
	 * @return void
	 */
	protected function publish(string $method, mixed $payload): void
	{
		$response = $this->getResponse($payload->args, $payload->data);
		$name = $this->getEventName($method);
		Events::update($name, $response);

		// Broadcast each action to the storage target to be used as a global event.
		Events::update('Storage', (object)[
			'target' => $this->target,
			'method' => $method,
			'args' => $response->args,
			'data' => $response->data
		]);
	}
}
