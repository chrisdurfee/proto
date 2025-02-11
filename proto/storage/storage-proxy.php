<?php declare(strict_types=1);
namespace Proto\Storage;

use Proto\Events\EventProxy;
use Proto\Events\Events;
use Proto\Models\ModelInterface;

/**
 * StorageProxy
 *
 * This will create a storage proxy object that will
 * dispatch an event for all of the actions the
 * storage layer is calling.
 *
 * This makes it so that developers do not need to
 * dispatch events to track when the storage is being
 * modified.
 *
 * @package Proto\Storage
 */
class StorageProxy extends EventProxy
{
    /**
     * @var ModelInterface $model
     */
    protected ModelInterface $model;

    /**
     * This will set up the storage proxy.
     *
     * @param ModelInterface $model
     * @param object $storage
     * @return void
     */
    public function __construct(ModelInterface &$model, object &$storage)
    {
        $this->model = $model;

        $target = $this->getModelName($model);
        parent::__construct($target, $storage);
    }

    /**
     * This will get the model name.
     *
     * @param object $model
     * @return string
     */
    protected function getModelName(object $model): string
    {
        $reflect = new \ReflectionClass($model);
        return $reflect->getShortName();
    }

    /**
     * This will get the event payload.
     *
     * @param array $args
     * @param mixed $result
     * @return object
     */
    protected function getResponse(array $args, mixed $result = false): object
    {
        if (!is_object($result) && !is_array($result))
        {
            $data = $this->model->getData();
        }
        else
        {
            $data = null;
            $response = $result->rows ?? $result->row ?? $result;
            if ($result)
            {
                $items = is_array($response)? $response : [$response];
                $data = $this->model->convertRows($items);
            }
        }

        return (object)[
            'args' => $args,
            'data' => $data
        ];
    }

    /**
     * This will publish the event.
     *
     * @param string $method
     * @param mixed $payload
     * @return void
     */
    protected function publish(string $method, mixed $payload): void
    {
        $response = $this->getResponse($payload->args, $payload->data);

        $name = $this->getEventName($method);
        Events::update($name, $response);

        /* this will broadcast each action to the storage target
        to be used as a global event. */
        Events::update('Storage', (object)[
            'target' => $this->target,
            'method' => $method,
            'args' => $response->args,
            'data' => $response->data
        ]);
    }
}
