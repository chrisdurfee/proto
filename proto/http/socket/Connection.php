<?php declare(strict_types=1);
namespace Proto\Http\Socket;

/**
 * Connection
 *
 * Represents a socket connection, allowing reading and writing of data.
 *
 * @package Proto\Http\Socket
 */
class Connection extends SocketHandler
{
	/**
	 * Maximum data length to read from the socket.
	 */
	protected const MAX_LENGTH = 1500;

	/**
	 * Initializes a new connection.
	 *
	 * @param StreamSocket $socket The socket instance.
	 * @return void
	 */
	public function __construct(
		protected readonly StreamSocket $socket
	)
	{
		parent::__construct();
	}

	/**
	 * Make the underlying socket blocking or non-blocking.
	 *
	 * @param bool $enable Whether to enable blocking mode.
	 * @return bool True on success, false on failure.
	 */
	public function blocking(bool $enable): bool
	{
		return $this->socket->setBlocking($enable);
	}

	/**
	 * Reads data from the socket.
	 *
	 * @return string|null The read data, or null on failure.
	 */
	public function read(): ?string
	{
		$response = $this->socket->receiveFrom(self::MAX_LENGTH);
		if ($response === false)
		{
			$this->error('Unable to read from the socket.');
			return null;
		}

		$this->emit('data', $response);
		return $response;
	}

	/**
	 * Writes data to the socket.
	 *
	 * @param string|null $data The data to write.
	 * @return int The number of bytes written.
	 * @throws \RuntimeException If writing to the socket fails.
	 */
	public function write(?string $data): int
	{
		if ($data === null || $data === '')
		{
			return 0; // Avoid sending empty data
		}

		$result = $this->socket->sendTo($data);
		if ($result === false)
		{
			$this->error('Unable to write to the socket.');
			return 0;
		}

		return $result;
	}
}