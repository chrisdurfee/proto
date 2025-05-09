<?php declare(strict_types=1);
namespace Proto\Http\ServerEvents;

use Proto\Events\EventEmitter;
use Proto\Http\Loop\FiberEvent;
use Proto\Http\Router\StreamResponse;
use Proto\Http\Loop\EventLoop;
use Proto\Http\Loop\UpdateEvent;

/**
 * ServerEvents
 *
 * Implements Server-Sent Events (SSE) for real-time data streaming.
 *
 * @package Proto\Http\ServerEvents
 */
class ServerEvents extends EventEmitter
{
	/**
	 * The StreamResponse instance.
	 *
	 * @var StreamResponse
	 */
	protected StreamResponse $response;

	/**
	 * The connection status.
	 *
	 * @var bool
	 */
	protected bool $connected = true;

	/**
	 * The EventLoop instance.
	 *
	 * @var EventLoop
	 */
	protected EventLoop $loop;

	/**
	 * Constructs a ServerEvents instance.
	 *
	 * @param int $interval The interval between event loop ticks in seconds.
	 */
	public function __construct(int $interval = 200)
	{
		parent::__construct();
		$this->initialize($interval);
	}

	/**
	 * Initializes the ServerEvents instance.
	 *
	 * @param int $interval The event loop interval in seconds.
	 * @return void
	 */
	protected function initialize(int $interval): void
	{
		$this->setupResponse();
		$this->loop = new EventLoop($interval);

		$this->emit('connection', $this->loop);
	}

	/**
	 * Starts the Server-Sent Events loop.
	 *
	 * @param callable $callback The callback function to execute.
	 * @return self
	 */
	public function start(callable $callback): self
	{
		$callback($this->loop);
		$this->runLoop();

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
		$this->response->sendHeaders($SUCCESS_CODE);
	}

	/**
	 * Checks if the server is connected.
	 *
	 * @return bool
	 */
	public function isConnected(): bool
	{
		return $this->connected;
	}

	/**
	 * Runs the event loop and handles disconnection.
	 *
	 * @return void
	 */
	protected function runLoop(): void
	{
		$this->loop->loop();
		$this->shutdown();
	}

	/**
	 * Streams data to the client using the event loop.
	 *
	 * @param callable $callback
	 * @return self
	 */
	public function stream(callable $callback): self
	{
		return $this->start(function(EventLoop $loop) use ($callback)
		{
			$loop->addEvent(new FiberEvent(function(FiberEvent $event) use ($callback, $loop)
			{
				$result = $callback($event);
				$loop->end(); // Runs only once
				return $result;
			}));
		});
	}

	/**
	 * Stops the server and emits the 'close' event.
	 *
	 * @return void
	 */
	protected function shutdown(): void
	{
		$this->connected = false;
		$this->emit('close');
	}
}