<?php declare(strict_types=1);
namespace Proto\Http\Socket;

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
	 * Initializes the server.
	 *
	 * @param string $address The server address (e.g., "127.0.0.1").
	 * @param int $port The server port.
	 */
	public function __construct(string $address, int $port)
	{
		parent::__construct();
		$this->preventTimeout();
		$this->socket = StreamSocket::server("{$address}:{$port}");
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
	 * @return void
	 */
	protected function listen(): void
	{
		while ($this->isConnected())
		{
			$connection = $this->accept();

			if ($connection === null)
			{
				usleep(500000); // Prevent CPU overuse (wait 0.5s before retrying)
				continue;
			}

			while ($this->isConnected())
			{
				$input = $connection->read();

				if ($input === null)
				{
					break; // Disconnect client if read fails
				}

				if ($input === 'exit')
				{
					$connection->close();
					$this->shutdown();
					break 2; // Exit both loops
				}

				usleep(100000); // Reduce CPU load (wait 0.1s)
			}

			$connection->close();
		}
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
}