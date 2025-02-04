<?php declare(strict_types=1);
namespace Proto\Http\Socket;

/**
 * Connection
 *
 * This class represents a connection and provides methods to read and write data.
 *
 * @package Proto\Http\Socket
 */
class Connection extends SocketHandler
{
	/**
	 * This will be the maximum length of the data to be read from the socket.
	 */
	protected const MAX_LENGTH = 1500;

	/**
	 * Connection constructor.
	 *
	 * @param SocketInterface $socket
	 * @return void
	 */
	public function __construct(SocketInterface $socket)
	{
		parent::__construct();
		$this->socket = $socket;
	}

	/**
	 * Read data from the socket.
	 *
	 * @return string|null Returns the read data or null if an error occurs.
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
	 * Write data to the socket.
	 *
	 * @param string|null $data The data to write.
	 * @return int|null Returns the number of bytes written or null if an error occurs.
	 */
	public function write(?string $data): ?int
	{
		$result = $this->socket->sendTo($data);
		if ($result === false)
		{
			$this->error('Unable to write to the socket.');
			return null;
		}

		return $result;
	}
}