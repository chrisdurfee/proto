<?php declare(strict_types=1);
namespace Proto\Http\ServerEvents;

use Proto\Events\EventEmitter;
use Proto\Http\Router\StreamResponse;
use Proto\Http\Loop\EventLoop;
use Proto\Http\Loop\UpdateEvent;

/**
 * ServerEvents
 *
 * Represents a Server-Sent Events implementation.
 *
 * @package Proto\Http\ServerEvents
 */
class ServerEvents extends EventEmitter
{
	/**
	 * The StreamResponse instance.
	 *
	 * @var StreamResponse $response
	 */
	protected StreamResponse $response;

	/**
	 * The connection status.
	 *
	 * @var bool $connected
	 */
	protected bool $connected = false;

	/**
	 * The EventLoop instance.
	 *
	 * @var EventLoop $loop
	 */
	protected EventLoop $loop;

	/**
	 * Constructs a ServerEvents instance.
	 *
	 * @param int $interval The interval between event loop ticks in seconds.
	 * @return void
	 */
	public function __construct(int $interval = 10)
	{
		parent::__construct();
		$this->init($interval);
	}

	/**
	 * Initializes the ServerEvents instance.
	 *
	 * @param int $interval The interval between event loop ticks in seconds.
	 * @return void
	 */
	protected function init(int $interval): void
	{
		$this->setupResponse();

		$loop = $this->setupLoop($interval);
		$this->emit('connection', $loop);
	}

	/**
	 * Starts the Server-Sent Events loop.
	 *
	 * @param callable $callback The callback function to execute.
	 * @return self
	 */
	public function start(callable $callback): self
	{
		if (!\is_callable($callback))
		{
			return $this;
		}

		$callback($this->loop);

		$this->loop();
		return $this;
	}

	/**
	 * Sets up the StreamResponse instance.
	 *
	 * @return void
	 */
	protected function setupResponse(): void
	{
		$SUCCESS_CODE = 200;
		$this->response = new StreamResponse();
		$this->response->headers($SUCCESS_CODE);
	}

	/**
	 * Sets up the EventLoop instance.
	 *
	 * @param int $interval The interval between event loop ticks in seconds.
	 * @return EventLoop
	 */
	protected function setupLoop(int $interval): EventLoop
	{
		return ($this->loop = new EventLoop($interval));
	}

	/**
	 * Checks if the server is connected.
	 *
	 * @return bool
	 */
	protected function isConnected(): bool
	{
		return $this->connected;
	}

	/**
	 * Starts the event loop.
	 *
	 * @return void
	 */
	protected function loop(): void
	{
		$this->loop->loop();
		$this->close();
	}

	/**
	 * This will stream the data to the client.
	 *
	 * @param callable $callback
	 * @return self
	 */
	public function stream(callable $callback): self
	{
		$this->start(function($loop) use ($callback)
		{
			$loop->addEvent(new UpdateEvent(function(UpdateEvent $event) use ($callback, $loop)
			{
				$result = $callback($event);

				/**
				 * This will only run the loop once.
				 */
				$loop->end();
				return $result;
			}));
		});

		return $this;
	}

	/**
	 * Stops the server and emits the 'close' event.
	 *
	 * @return void
	 */
	protected function close(): void
	{
		$this->connected = false;
		$this->emit('close');
	}
}