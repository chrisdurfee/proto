<?php declare(strict_types=1);
namespace Proto\Http\Socket;

use Proto\Events\EventEmitter;

/**
 * SocketService
 *
 * Base class for managing socket streams and connections.
 *
 * @package Proto\Http\Socket
 */
abstract class SocketService extends EventEmitter
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
	 * Accepts a new connection on the stream.
	 *
	 * @param float|null $timeout The connection timeout.
	 * @param string|null $peerName The peer name (reference variable).
	 * @return Connection|null The new connection instance, or null if failed.
	 */
	public function accept(?float $timeout = null, string &$peerName = null): ?Connection
	{
		$socket = $this->socket->accept($timeout, $peerName);
		if ($socket === false)
		{
			$this->error('Unable to create new connection.');
			return null;
		}

		$connection = new Connection($socket);
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
	 * Validates the socket response.
	 *
	 * @param mixed $response The response to validate.
	 * @param string|null $message The error message (if needed).
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