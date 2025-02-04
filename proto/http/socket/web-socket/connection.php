<?php declare(strict_types=1);
namespace Proto\Http\Socket\WebSocket;

use Proto\Http\Socket\WebSocket\Headers;
use Proto\Http\Socket\Connection as BaseConnection;

/**
 * Connection
 *
 * This class represents a connection and provides methods to read and write data.
 *
 * @package Proto\Http\Socket\WebSocket
 */
class Connection extends BaseConnection
{
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

        /**
         * This will unseal the message.
         */
        $response = MessageHandler::unseal($response);
		$this->emit('data', $response);
		return $response;
	}

	/**
	 * This will upgrade the connection to a WebSocket connection.
	 *
	 * @return void
	 */
	public function upgrade(): void
	{
		$request = $this->socket->receiveFrom(self::MAX_LENGTH);
		if ($request === null)
		{
			return;
		}

		$headers = Headers::get($request);
		if (!isset($headers))
		{
			return;
		}

		$this->socket->sendTo($headers);
	}

	/**
	 * Write data to the socket.
	 *
	 * @param string|null $data The data to write.
	 * @return int|null Returns the number of bytes written or null if an error occurs.
	 */
	public function write(?string $data): ?int
	{
        /**
         * This will seal the message.
         */
        $data = MessageHandler::seal($data);
		$result = $this->socket->sendTo($data);
		if ($result === false)
		{
			$this->error('Unable to write to the socket.');
			return null;
		}

		return $result;
	}
}