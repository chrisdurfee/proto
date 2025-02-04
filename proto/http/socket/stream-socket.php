<?php declare(strict_types=1);
namespace Proto\Http\Socket;

/**
 * StreamSocket
 *
 * Creates a stream socket.
 *
 * @package Proto\Http\Socket
 */
class StreamSocket implements SocketInterface
{
	/**
	 * @var resource|bool $stream
	 */
	protected $stream;

	/**
	 * StreamSocket constructor.
	 *
	 * @param resource|bool $stream
	 * @return void
	 */
	public function __construct($stream)
	{
		$this->setStream($stream);
	}

	/**
	 * Create a stream socket server.
	 *
	 * @param string $address
	 * @param int|null $errorCode
	 * @param string|null $errorMessage
	 * @return SocketInterface
	 */
	public static function server(
		string $address,
		?int &$errorCode = null,
		?string &$errorMessage = null
	): SocketInterface
	{
		$stream = stream_socket_server($address, $errorCode, $errorMessage);
		return new static($stream);
	}

	/**
	 * Create a stream socket client.
	 *
	 * @param string $address
	 * @param int|null $errorCode
	 * @param string|null $errorMessage
	 * @return SocketInterface
	 */
	public static function client(
		string $address,
		?int &$errorCode = null,
		?string &$errorMessage = null
	): SocketInterface
	{
		$stream = stream_socket_client($address, $errorCode, $errorMessage);
		return new static($stream);
	}

	/**
	 * Create a pair of connected, indistinguishable socket streams.
	 *
	 * @param int $domain
	 * @param int $type
	 * @param int $protocol
	 * @return array|false
	 */
	public static function pair(int $domain, int $type, int $protocol)
	{
		$streams = stream_socket_pair($domain, $type, $protocol);
		return (!$streams)? $streams : array_map(fn($item) => new static($item), $streams);
	}

	/**
	 * Set the stream.
	 *
	 * @param resource|bool $stream
	 * @return void
	 */
	protected function setStream($stream): void
	{
		$this->stream = $stream;
	}

	/**
	 * Set blocking mode on the stream.
	 *
	 * @return bool
	 */
	public function setBlocking(bool $enable): bool
	{
		return stream_set_blocking($this->stream, $enable);
	}

	/**
	 * Set the chunk size on the stream.
	 *
	 * @return int|false
	 */
	public function setChunkSize(int $size)
	{
		return stream_set_chunk_size($this->stream, $size);
	}

	/**
	 * Set the write buffer on the stream.
	 *
	 * @return int|false
	 */
	public function setWriteBuffer(int $size)
	{
		return stream_set_write_buffer($this->stream, $size);
	}

	/**
	 * Set the timeout period on the stream.
	 *
	 * @param int $seconds
	 * @param int $microseconds
	 * @return bool
	 */
	public function setTimeout(int $seconds, int $microseconds = 0): bool
	{
		return stream_set_timeout($this->stream, $seconds, $microseconds);
	}

	/**
	 * Enable or disable crypto on the stream.
	 *
	 * @param bool $enable
	 * @param int|null $cryptoMethod
	 * @return bool
	 */
	public function enableCrypto(
		bool $enable,
		?int $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_SERVER,
	): bool
	{
		return stream_socket_enable_crypto($this->stream, $enable, $cryptoMethod);
	}

	/**
	 * Get the name of the stream.
	 *
	 * @param bool $remote
	 * @return string|false
	 */
	public function getName(bool $remote = true)
	{
		return stream_socket_get_name($this->stream, $remote);
	}

	/**
	* Read the specified number of bytes from the socket.
	*
	* @param int $length
	* @param int $offset
	* @return string|false
	*/
	public function read(int $length, int $offset = -1)
	{
		return stream_get_contents($this->stream, $length, $offset);
	}

	/**
	* Write data to the socket.
	*
	* @param string|null $data
	* @param int|null $length
	* @return int|false
	*/
	public function write(?string $data, ?int $length = null)
	{
		return fwrite($this->stream, $data, $length);
	}

	/**
	* Receive data from the stream.
	*
	* @param int $length
	* @param int $flag
	* @param string|null $address
	* @return string|false
	*/
	public function receiveFrom(int $length, int $flag = 0, ?string $address = null)
	{
		return stream_socket_recvfrom($this->stream, $length, $flag, $address);
	}

	/**
	* Send data to the stream.
	*
	* @param string|null $data
	* @param int $flag
	* @param string $address
	* @return int|false
	*/
	public function sendTo(?string $data, int $flag = 0, string $address = "")
	{
		return stream_socket_sendto($this->stream, $data, $flag, $address);
	}

	/**
	* Shutdown the stream.
	*
	* @param int $mode
	* @return bool
	*/
	public function shutDown(int $mode = 2): bool
	{
		return stream_socket_shutdown($this->stream, $mode);
	}

	/**
	* Accept a connection on the stream.
	*
	* @param float|null $timeout
	* @param string|null $peerName
	* @return SocketInterface|null
	*/
	public function accept(?float $timeout = null, string &$peerName = null): ?SocketInterface
	{
		$stream = @stream_socket_accept($this->stream, $timeout, $peerName);
		if (!$stream)
		{
			return null;
		}
		return new static($stream);
	}

	/**
	* Close the stream.
	*
	* @return void
	*/
	public function close(): void
	{
		fclose($this->stream);
	}
}