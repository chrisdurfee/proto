<?php declare(strict_types=1);
namespace Proto\Http\Socket;

use Proto\Http\Loop\EventLoop;
use Proto\Http\Loop\AsyncEvent;
use Fiber;

/**
 * Server
 *
 * Represents a server socket and manages client connections.
 *
 * @package Proto\Http\Socket
 */
class Server extends SocketHandler
{
	/**
	 * Indicates whether the server is running.
	 *
	 * @var bool
	 */
	protected bool $connected = true;

	/**
	 * The socket instance.
	 *
	 * @var StreamSocket
	 */
	protected readonly StreamSocket $socket;

	/**
	 * The event loop instance.
	 *
	 * @var EventLoop
	 */
	protected EventLoop $loop;

	/**
	 * Initializes the server.
	 *
	 * @param string $address The server address (e.g., "127.0.0.1").
	 * @param int $port The server port.
	 */
	public function __construct(string $address, int $port)
	{
		parent::__construct();
		$this->preventTimeout();

		/**
		 * This will set up a stream socket that is non-blocking.
		 */
		$this->socket = StreamSocket::server("{$address}:{$port}");
		$this->blocking(false);
	}

	/**
	 * Prevents server from timing out.
	 *
	 * @return void
	 */
	protected function preventTimeout(): void
	{
		set_time_limit(0);
	}

	/**
	 * Sets the socket blocking mode.
	 *
	 * @param bool $enable Whether to enable blocking.
	 * @return bool
	 */
	public function blocking(bool $enable): bool
	{
		return $this->socket->setBlocking($enable);
	}

	/**
	 * Sets the chunk size for data transfer.
	 *
	 * @param int $size The chunk size in bytes.
	 * @return int|false
	 */
	public function chunk(int $size): int|false
	{
		return $this->socket->setChunkSize($size);
	}

	/**
	 * Sets the write buffer size.
	 *
	 * @param int $size The buffer size in bytes.
	 * @return int|false
	 */
	public function buffer(int $size): int|false
	{
		return $this->socket->setWriteBuffer($size);
	}

	/**
	 * Sets the timeout period for connections.
	 *
	 * @param int $seconds Timeout in seconds.
	 * @param int $microseconds Timeout in microseconds (optional).
	 * @return bool
	 */
	public function timeout(int $seconds, int $microseconds = 0): bool
	{
		return $this->socket->setTimeout($seconds, $microseconds);
	}

	/**
	 * Enables or disables SSL/TLS encryption on the socket.
	 *
	 * @param bool $enable Whether to enable encryption.
	 * @return bool
	 */
	public function secure(bool $enable = true): bool
	{
		return $this->socket->enableCrypto($enable, STREAM_CRYPTO_METHOD_TLSv1_2_SERVER);
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
	 * Listens for incoming connections and processes client requests.
	 *
	 * @param int $tickInterval The interval for processing events (default: 200ms).
	 * @return void
	 */
	protected function listen(int $tickInterval = 200): void
	{
		$this->loop = new EventLoop($tickInterval);

		/**
		 * Each tick we try one non-blocking accept.
		 */
		$this->blocking(false);
		$this->loop->addEvent(new AsyncEvent(fn() => $this->accept(0.0)));

		/**
		 * When the accept emits 'connection', spawn a new reader fiber.
		 */
		$this->on('connection', function(Connection $conn)
		{
			// Make sure this socket is non-blocking too
			$conn->blocking(false);

			// One fiber per connection
			$this->loop->addEvent(new AsyncEvent(function() use ($conn)
			{
				while (true)
				{
					$data = $conn->read();
					$ShouldExit = (trim((string)$data) === 'exit');
					if ($data === null || $ShouldExit)
					{
						$conn->close();
						if ($ShouldExit)
						{
							$this->shutdown();
						}
						return;
					}
					Fiber::suspend();
				}
			}));
		});

		// This will start the loop
		$this->loop->loop();
	}

	/**
	 * Starts the server and begins listening for connections.
	 *
	 * @return void
	 */
	public function run(): void
	{
		$this->listen();
	}

	/**
	 * Shuts down the stream.
	 *
	 * @param int $mode The shutdown mode (default: 2).
	 * @return void
	 */
	public function shutdown(int $mode = 2): void
	{
		parent::shutdown($mode);
		$this->loop->end();
	}
}