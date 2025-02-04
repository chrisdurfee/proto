<?php declare(strict_types=1);
namespace Proto\Http\Socket;

/**
 * Server
 *
 * This class represents a server socket and provides methods to manage its connections.
 *
 * @package Proto\Http\Socket
 */
class Server extends SocketHandler
{
	/**
	 * @var bool $connected
	 */
	protected bool $connected = true;

	/**
	 * Server constructor.
	 *
	 * @param string $address
	 * @param int $port
	 * @return void
	 */
	public function __construct(string $address, int $port)
	{
		parent::__construct();
		$this->preventTimeOut();
		$this->socket = StreamSocket::server($address . ':' . $port);
	}

	/**
	 * Prevent the server from timing out.
	 *
	 * @return void
	 */
	protected function preventTimeOut(): void
	{
		set_time_limit(0);
	}

	/**
	 * Set blocking mode on the stream.
	 *
	 * @param bool $enable
	 * @return bool
	 */
	public function blocking(bool $enable): bool
	{
		$result = $this->socket->setBlocking($enable);
		return $this->checkResponse($result, 'Unable to update blocking.');
	}

	/**
	 * Set the chunk size on the stream.
	 *
	 * @param int $size
	 * @return int|false
	 */
	public function chunk(int $size)
	{
		return $this->socket->setChunkSize($size);
	}

	/**
	 * Set the write buffer on the stream.
	 *
	 * @param int $size
	 * @return int|false
	 */
	public function buffer(int $size)
	{
		return $this->socket->setWriteBuffer($size);
	}

	/**
	 * Set the timeout period on the stream.
	 *
	 * @param int $seconds
	 * @param int $microseconds
	 * @return bool
	 */
	public function timeout(int $seconds, int $microseconds = 0): bool
	{
		$result = $this->socket->setTimeout($seconds, $microseconds);
		return $this->checkResponse($result, 'Unable to update the timeout.');
	}

	/**
	 * Enable or disable crypto on the stream.
	 *
	 * @param bool $enable
	 * @return bool
	 */
	public function secure(bool $enable = true): bool
	{
		$result = $this->socket->enableCrypto($enable, STREAM_CRYPTO_METHOD_TLSv1_2_SERVER);
		return $this->checkResponse($result, 'Unable to update the ssl settings.');
	}

	/**
	 * Check if the server is connected.
	 *
	 * @return bool
	 */
	protected function isConnected(): bool
	{
		return $this->connected;
	}

	/**
	 * Listen to the socket for incoming connections.
	 *
	 * @return void
	 */
	protected function listen(): void
	{
		while ($this->isConnected())
		{
			$connection = $this->accept();
			if (is_null($connection))
			{
				continue;
			}

			while (true)
			{
				$input = $connection->read();
				if ($input === 'exit')
				{
					$connection->close();
					$this->stop();
				}
				sleep(1);
			}
		}
	}

	/**
	 * This will run the server.
	 *
	 * @return void
	 */
	public function run(): void
	{
		$this->listen();
	}

	/**
	 * Stop the server.
	 *
	 * @return void
	 */
	protected function stop(): void
	{
		$this->close();
		$this->connected = false;
	}
}