<?php declare(strict_types=1);
namespace Proto\Http\Socket;

use Proto\Events\EventEmitter;
use RuntimeException;

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
	 * @throws RuntimeException If unable to retrieve the address.
	 */
	public function getRemoteAddress(bool $remote): string
	{
		$address = $this->socket->getName($remote);
		if ($address === false)
		{
			throw new RuntimeException('Unable to get the remote address.');
		}

		return $address;
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
		if ($socket === false)
		{
			$this->emitError('Unable to accept new connection.', ['peerName' => $peerName]);
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
	 * Throws an exception if the response is invalid.
	 *
	 * @param mixed $response The response to validate.
	 * @param string|null $message The error message (if needed).
	 * @return mixed The original response if valid.
	 * @throws RuntimeException If response is invalid.
	 */
	protected function validateResponse(mixed $response, ?string $message = null): mixed
	{
		if ($response === false)
		{
			throw new RuntimeException($message ?? 'An unexpected socket error occurred.');
		}

		return $response;
	}

	/**
	 * Emits an error event with context.
	 *
	 * @param string $message The error message.
	 * @param array $context Additional error context.
	 * @return void
	 */
	protected function emitError(string $message, array $context = []): void
	{
		$this->emit('error', [
			'message' => $message,
			'context' => $context
		]);

		trigger_error($message, E_USER_WARNING);
	}
}