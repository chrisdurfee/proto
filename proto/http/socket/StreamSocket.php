<?php declare(strict_types=1);
namespace Proto\Http\Socket;

/**
 * StreamSocket
 *
 * Manages stream-based socket connections.
 *
 * @package Proto\Http\Socket
 */
class StreamSocket implements SocketInterface
{
	/**
	 * The stream socket resource.
	 *
	 * @var resource
	 */
	protected readonly mixed $stream;

	/**
	 * Initializes a stream socket instance.
	 *
	 * @param resource $stream The socket resource.
	 */
	public function __construct(mixed $stream)
	{
		$this->stream = self::validateStream($stream);
	}

	/**
	 * Creates a stream socket server.
	 *
	 * @param string $address The server address.
	 * @param int|null $errorCode Error code reference.
	 * @param string|null $errorMessage Error message reference.
	 * @return SocketInterface
	 */
	public static function server(
		string $address,
		?int &$errorCode = null,
		?string &$errorMessage = null
	): SocketInterface
	{
		$stream = stream_socket_server($address, $errorCode, $errorMessage);
		return new static(self::validateStream($stream));
	}

	/**
	 * Creates a stream socket client.
	 *
	 * @param string $address The client address.
	 * @param int|null $errorCode Error code reference.
	 * @param string|null $errorMessage Error message reference.
	 * @return SocketInterface
	 */
	public static function client(
		string $address,
		?int &$errorCode = null,
		?string &$errorMessage = null
	): SocketInterface
	{
		$stream = stream_socket_client($address, $errorCode, $errorMessage);
		return new static(self::validateStream($stream));
	}

	/**
	 * Creates a pair of connected, indistinguishable socket streams.
	 *
	 * @param int $domain The domain.
	 * @param int $type The type.
	 * @param int $protocol The protocol.
	 * @return array|false Returns an array of `StreamSocket` instances, or false on failure.
	 */
	public static function pair(int $domain, int $type, int $protocol): array|false
	{
		$streams = stream_socket_pair($domain, $type, $protocol);
		return $streams ? array_map(fn($item) => new static($item), $streams) : false;
	}

	/**
	 * Validates the stream resource.
	 *
	 * @param mixed $stream The stream to validate.
	 * @return mixed The validated stream.
	 */
	protected static function validateStream(mixed $stream): mixed
	{
		if (!is_resource($stream))
		{
			exit('Error: Invalid stream socket.');
		}

		return $stream;
	}

	/**
	 * Sets the stream blocking mode.
	 *
	 * @param bool $enable Whether to enable blocking.
	 * @return bool
	 */
	public function setBlocking(bool $enable): bool
	{
		return stream_set_blocking($this->stream, $enable);
	}

	/**
	 * Sets the chunk size for the stream.
	 *
	 * @param int $size The chunk size in bytes.
	 * @return int|false
	 */
	public function setChunkSize(int $size): int|false
	{
		return stream_set_chunk_size($this->stream, $size);
	}

	/**
	 * Sets the write buffer for the stream.
	 *
	 * @param int $size The buffer size in bytes.
	 * @return int|false
	 */
	public function setWriteBuffer(int $size): int|false
	{
		return stream_set_write_buffer($this->stream, $size);
	}

	/**
	 * Sets the timeout period for the stream.
	 *
	 * @param int $seconds Timeout in seconds.
	 * @param int $microseconds Timeout in microseconds.
	 * @return bool
	 */
	public function setTimeout(int $seconds, int $microseconds = 0): bool
	{
		return stream_set_timeout($this->stream, $seconds, $microseconds);
	}

	/**
	 * Enables or disables crypto on the stream.
	 *
	 * @param bool $enable Whether to enable crypto.
	 * @param int|null $cryptoMethod The crypto method to use.
	 * @return bool
	 */
	public function enableCrypto(bool $enable, ?int $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_SERVER): bool
	{
		return stream_socket_enable_crypto($this->stream, $enable, $cryptoMethod);
	}

	/**
	 * Gets the name of the stream.
	 *
	 * @param bool $remote Whether to fetch the remote name.
	 * @return string|false
	 */
	public function getName(bool $remote = true): string|false
	{
		return stream_socket_get_name($this->stream, $remote);
	}

	/**
	 * Reads a specified number of bytes from the stream.
	 *
	 * @param int $length The number of bytes to read.
	 * @param int $offset The offset position.
	 * @return string|false
	 */
	public function read(int $length, int $offset = -1): string|false
	{
		return stream_get_contents($this->stream, $length, $offset);
	}

	/**
	 * Writes data to the stream.
	 *
	 * @param string $data The data to write.
	 * @param int|null $length The number of bytes to write.
	 * @return int|false
	 */
	public function write(string $data, ?int $length = null): int|false
	{
		return fwrite($this->stream, $data, $length ?? strlen($data));
	}

	/**
	 * Receives data from the stream.
	 *
	 * @param int $length The number of bytes to receive.
	 * @param int $flag The receive flag.
	 * @param string|null $address The address reference.
	 * @return string|false
	 */
	public function receiveFrom(int $length, int $flag = 0, ?string &$address = null): string|false
	{
		return stream_socket_recvfrom($this->stream, $length, $flag, $address);
	}

	/**
	 * Sends data to the stream.
	 *
	 * @param string $data The data to send.
	 * @param int $flag The send flag.
	 * @param string $address The destination address.
	 * @return int|false
	 */
	public function sendTo(string $data, int $flag = 0, string $address = ""): int|false
	{
		return stream_socket_sendto($this->stream, $data, $flag, $address);
	}

	/**
	 * Shuts down the stream.
	 *
	 * @param int $mode The shutdown mode.
	 * @return bool
	 */
	public function shutdown(int $mode = 2): bool
	{
		return stream_socket_shutdown($this->stream, $mode);
	}

	/**
	 * Accepts a connection on the stream.
	 *
	 * @param float|null $timeout The timeout period.
	 * @param string|null $peerName The peer name reference.
	 * @return SocketInterface|null
	 */
	public function accept(?float $timeout = null, string &$peerName = null): ?SocketInterface
	{
		$stream = @stream_socket_accept($this->stream, $timeout, $peerName);
		return $stream ? new static($stream) : null;
	}

	/**
	 * Closes the stream.
	 *
	 * @return void
	 */
	public function close(): void
	{
		if (is_resource($this->stream))
		{
			fclose($this->stream);
		}
	}
}