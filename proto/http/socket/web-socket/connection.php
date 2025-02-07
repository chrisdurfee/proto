<?php declare(strict_types=1);
namespace Proto\Http\Socket\WebSocket;

use Proto\Http\Socket\Connection as BaseConnection;

/**
 * Class Connection
 *
 * Represents a WebSocket connection with methods for reading, writing, and upgrading connections.
 *
 * @package Proto\Http\Socket\WebSocket
 */
class Connection extends BaseConnection
{
	/**
	 * Reads data from the socket.
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

		$unsealedResponse = MessageHandler::unseal($response);
		$this->emit('data', $unsealedResponse);

		return $unsealedResponse;
	}

	/**
	 * Upgrades the connection to a WebSocket connection.
	 *
	 * @return void
	 */
	public function upgrade(): void
	{
		$request = $this->socket->receiveFrom(self::MAX_LENGTH);
		if (empty($request))
		{
			$this->error('No request received for upgrade.');
			return;
		}

		$headers = Headers::get($request);
		if ($headers === null)
		{
			$this->error('Invalid headers received during upgrade.');
			return;
		}

		$this->socket->sendTo($headers);
	}

	/**
	 * Writes data to the socket.
	 *
	 * @param string|null $data The data to write.
	 * @return int Returns the number of bytes written or 0 if an error occurs.
	 */
	public function write(?string $data): int
	{
		if ($data === null || $data === '')
		{
			$this->error('Attempted to write empty data to the socket.');
			return 0;
		}

		$sealedData = MessageHandler::seal($data);
		$result = $this->socket->sendTo($sealedData);
		if ($result === false)
		{
			$this->error('Unable to write to the socket.');
			return 0;
		}

		return $result;
	}
}