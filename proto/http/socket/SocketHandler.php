<?php declare(strict_types=1);
namespace Proto\Http\Socket;

use Proto\Events\EventEmitter;

/**
 * SocketHandler
 *
 * Base class for managing socket connections.
 *
 * @package Proto\Http\Socket
 */
abstract class SocketHandler extends EventEmitter
{
	/**
	 * The socket instance.
	 *
	 * @var StreamSocket
	 */
	protected readonly StreamSocket $socket;

	/**
	 * Retrieves the remote address of the stream.
	 *
	 * @param bool $remote Whether to fetch the remote address.
	 * @return string
	 */
	public function getRemoteAddress(bool $remote): string
	{
		$name = $this->socket->getName($remote);
		return $this->checkResponse($name, 'Unable to get the remote address.');
	}

	/**
	 * Shuts down the stream.
	 *
	 * @param int $mode The shutdown mode (default: 2).
	 * @return void
	 */
	public function shutdown(int $mode = 2): void
	{
		$this->socket->shutdown($mode);
		$this->emit('close');
	}

	/**
	 * Creates a connection instance.
	 *
	 * @param SocketInterface $socket The socket interface.
	 * @return Connection
	 */
	protected function createConnection(SocketInterface $socket): Connection
	{
		return new Connection($socket);
	}

	/**
	 * Accepts a new incoming connection.
	 *
	 * @param float|null $timeout The connection timeout.
	 * @param string|null $peerName The peer name (reference variable).
	 * @return Connection|null The new connection instance, or null if failed.
	 */
	public function accept(?float $timeout = null, string &$peerName = null): ?Connection
	{
		$socket = $this->socket->accept($timeout, $peerName);
		if (!$socket)
		{
			$this->error('Unable to create new connection.');
			return null;
		}

		$connection = $this->createConnection($socket);
		$this->emit('connection', $connection);
		return $connection;
	}

	/**
	 * Closes the socket connection.
	 *
	 * @return void
	 */
	public function close(): void
	{
		if (isset($this->socket))
		{
			$this->socket->close();
		}

		$this->emit('close');
	}

	/**
	 * Check the socket response.
	 *
	 * @param mixed $response
	 * @param string|null $message
	 * @return mixed
	 */
	protected function checkResponse(mixed $response, ?string $message = null): mixed
	{
		if (!$response)
		{
			$this->error($message);
		}
		return $response;
	}

	/**
	 * Emit an error event.
	 *
	 * @param string|null $message
	 * @return void
	 */
	protected function error(?string $message = null): void
	{
		$this->emit('error', [
			'message' => $message
		]);
	}
}