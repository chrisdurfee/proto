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
	 * The socket instance.
	 *
	 * @var SocketInterface
	 */
	protected readonly SocketInterface $socket;

	/**
	 * Initializes a new connection.
	 *
	 * @param SocketInterface $socket The socket instance.
	 */
	public function __construct(SocketInterface $socket)
	{
		parent::__construct();
		$this->socket = $socket;
	}

	/**
	 * Reads data from the socket.
	 *
	 * @return string|null The read data, or null on failure.
	 * @throws \RuntimeException If reading from the socket fails.
	 */
	public function read(): ?string
	{
		$response = $this->socket->receiveFrom(self::MAX_LENGTH);
		if ($response === false)
		{
			trigger_error('Socket read error: Unable to read from the socket.', E_USER_WARNING);
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
			trigger_error('Socket write error: Unable to write to the socket.', E_USER_WARNING);
			return 0;
		}

		return $result;
	}
}