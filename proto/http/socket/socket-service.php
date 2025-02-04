<?php declare(strict_types=1);
namespace Proto\Http\Socket;

use Proto\Events\EventEmitter;

/**
 * SocketService
 *
 * Defines the socket service.
 */
abstract class SocketService extends EventEmitter
{
	/**
	 * @var StreamSocket
	 */
	protected StreamSocket $socket;

	/**
	 * Get the remote address of the stream.
	 *
	 * @param bool $remote
	 * @return string
	 */
	public function getRemoteAddress(bool $remote): string
	{
		$name = $this->socket->getName($remote);
		return $this->checkResponse($name, 'Unable to get the remote address.');
	}

	/**
	 * Shutdown the stream.
	 *
	 * @param int $mode
	 * @return void
	 */
	public function shutDown(int $mode = 2): void
	{
		$this->socket->shutDown($mode);

		$this->emit('close');
	}

	/**
	 * Accept a connection on the stream.
	 *
	 * @param float|null $timeout
	 * @param string|null $peerName
	 * @return Connection|null
	 */
	public function accept(?float $timeout = null, string &$peerName = null): ?Connection
	{
		$socket = $this->socket->accept($timeout, $peerName);
		if (!$socket)
		{
			$this->error('Unable to create new connection.');
			return null;
		}

		$connection = new Connection($socket);
		$this->emit('connection', $connection);
		return $connection;
	}

	/**
	 * Close the stream.
	 *
	 * @return void
	 */
	public function close(): void
	{
		$this->socket->close();

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