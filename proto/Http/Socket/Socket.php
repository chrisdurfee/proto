<?php declare(strict_types=1);
namespace Proto\Http\Socket;

/**
 * Socket
 *
 * Handles socket creation and operations.
 *
 * @link https://www.php.net/manual/en/book.sockets.php
 * @package Proto\Http\Socket
 */
class Socket implements SocketInterface
{
	/**
	 * Initializes a socket instance.
	 *
	 * @param \Socket $socket The socket resource.
	 */
	public function __construct(
		protected readonly \Socket $socket
	)
	{
	}

	/**
	 * Creates a new socket.
	 *
	 * @param int $domain The socket domain.
	 * @param int $type The socket type.
	 * @param int $protocol The socket protocol.
	 * @return SocketInterface
	 */
	public static function create(int $domain, int $type, int $protocol): SocketInterface
	{
		$socket = socket_create($domain, $type, $protocol);
		return new static(self::validateSocket($socket));
	}

	/**
	 * Validates a socket instance.
	 *
	 * @param \Socket|bool $socket The socket to validate.
	 * @return \Socket
	 */
	protected static function validateSocket(\Socket|bool $socket): \Socket
	{
		if ($socket === false)
		{
			exit('Error: Unable to create socket.');
		}

		return $socket;
	}

	/**
	 * Sets blocking mode on the socket.
	 *
	 * @return bool
	 */
	public function setBlock(): bool
	{
		return socket_set_block($this->socket);
	}

	/**
	 * Sets non-blocking mode on the socket.
	 *
	 * @return bool
	 */
	public function setNonBlock(): bool
	{
		return socket_set_nonblock($this->socket);
	}

	/**
	 * Sets socket options.
	 *
	 * @param int $level The protocol level.
	 * @param int $option The option name.
	 * @param mixed $value The option value.
	 * @return bool
	 */
	public function setOptions(int $level, int $option, mixed $value): bool
	{
		return socket_set_option($this->socket, $level, $option, $value);
	}

	/**
	 * Connects the socket to a remote address.
	 *
	 * @param string $address The remote address.
	 * @param int|null $port The port (optional).
	 * @return bool
	 */
	public function connect(string $address, ?int $port = null): bool
	{
		return socket_connect($this->socket, $address, $port ?? 0);
	}

	/**
	 * Binds the socket to a local address.
	 *
	 * @param string $address The local address.
	 * @param int|null $port The port (optional).
	 * @return bool
	 */
	public function bind(string $address, ?int $port = null): bool
	{
		return socket_bind($this->socket, $address, $port ?? 0);
	}

	/**
	 * Reads data from the socket.
	 *
	 * @param int $length The number of bytes to read.
	 * @param int $mode The read mode (default: PHP_BINARY_READ).
	 * @return string|false
	 */
	public function read(int $length, int $mode = PHP_BINARY_READ): string|false
	{
		return socket_read($this->socket, $length, $mode);
	}

	/**
	 * Writes data to the socket.
	 *
	 * @param string $data The data to write.
	 * @param int|null $length The number of bytes to write (optional).
	 * @return int|false The number of bytes written, or false on failure.
	 */
	public function write(string $data, ?int $length = null): int|false
	{
		return socket_write($this->socket, $data, $length ?? strlen($data));
	}

	/**
	 * Receives data from the socket.
	 *
	 * @param string &$data The buffer to store received data.
	 * @param int $length The maximum number of bytes to receive.
	 * @param int $flags The receive flags.
	 * @return int|false The number of bytes received, or false on failure.
	 */
	public function receive(string &$data, int $length, int $flags): int|false
	{
		return socket_recv($this->socket, $data, $length, $flags);
	}

	/**
	 * Sends data over the socket.
	 *
	 * @param string $data The data to send.
	 * @param int $length The number of bytes to send.
	 * @param int $flags The send flags.
	 * @return int|false The number of bytes sent, or false on failure.
	 */
	public function send(string $data, int $length, int $flags): int|false
	{
		return socket_send($this->socket, $data, $length, $flags);
	}

	/**
	 * Starts listening for incoming connections.
	 *
	 * @param int $backlog The maximum backlog queue size.
	 * @return bool
	 */
	public function listen(int $backlog = 0): bool
	{
		return socket_listen($this->socket, $backlog);
	}

	/**
	 * Shuts down the socket.
	 *
	 * @param int $mode The shutdown mode (default: 2).
	 * @return bool
	 */
	public function shutdown(int $mode = 2): bool
	{
		return socket_shutdown($this->socket, $mode);
	}

	/**
	 * Accepts an incoming connection.
	 *
	 * @return SocketInterface
	 */
	public function accept(): SocketInterface
	{
		$socket = socket_accept($this->socket);
		return new static(self::validateSocket($socket));
	}

	/**
	 * Closes the socket.
	 *
	 * @return void
	 */
	public function close(): void
	{
		socket_close($this->socket);
	}
}