<?php declare(strict_types=1);
namespace Proto\Http\Socket;

/**
 * Socket
 *
 * This will create a socket.
 *
 * @link https://www.php.net/manual/en/book.sockets.php
 * @package Proto\Http\Socket
 */
class Socket implements SocketInterface
{
	/**
	 * @var \Socket|bool|resource $socket
	 */
	protected \Socket|bool $socket;

    /**
     * This will create a socket.
     *
     * @param \Socket|bool|resource $socket
     * @return void
     */
    public function __construct($socket)
    {
        $this->setSocket($socket);
    }

    /**
     * This will create a socket.
     *
     * @param int $domain
     * @param int $type
     * @param int $protocol
     * @return SocketInterface
     */
    public static function create(int $domain, int $type, int $protocol): SocketInterface
    {
        $socket = socket_create($domain, $type, $protocol);
        return new static($socket);
    }

    /**
     * This will set the socket.
     *
     * @param \Socket|bool|resource $socket
     * @return void
     */
    protected function setSocket($socket): void
    {
        $this->socket = $socket;
    }

    /**
     * This will set blocking mode on the socket.
     *
     * @return bool
     */
    public function setBlock(): bool
    {
        return socket_set_block($this->socket);
    }

    /**
     * This will set non blocking mode on the socket.
     *
     * @return bool
     */
    public function setNonBlock(): bool
    {
        return socket_set_nonblock($this->socket);
    }

    /**
     * This will set the options on the socket.
     *
     * @return bool
     */
    public function setOptions(int $level, int $option, $value): bool
    {
        return socket_set_option($this->socket, $level, $option, $value);
    }

    /**
     * This will connect to the socket.
     *
     * @param string $address
     * @param int|null $port
     * @return bool
     */
    public function connect(string $address, ?int $port = null): bool
    {
        return socket_connect($this->socket, $address, $port);
    }

    /**
     * This will bind a name to the socket.
     *
     * @param string $address
     * @param int|null $port
     * @return bool
     */
    public function bind(string $address, ?int $port = null): bool
    {
        return socket_bind($this->socket, $address, $port);
    }

    /**
     * This will read the maximum length bytes from the socket.
     *
     * @param int $length
     * @param int $mode
     * @return string|false
     */
    public function read(int $length, int $mode = PHP_BINARY_READ)
    {
        return socket_read($this->socket, $length, $mode);
    }

    /**
     * This writes data to the socket.
     *
     * @param string|null $data
     * @param int|null $length
     * @param int $flag
     * @return int|false
     */
    public function write(?string $data, ?int $length = null)
    {
        return socket_write($this->socket, $data, $length);
    }

    /**
     * This receives data from the socket.
     *
     * @param string|null $data
     * @param int $length
     * @param int $flag
     * @return int|false
     */
    public function receive(?string $data, int $length, int $flag)
    {
        return socket_recv($this->socket, $data, $length, $flag);
    }

    /**
     * This sends data from the socket.
     *
     * @param string|null $data
     * @param int $length
     * @param int $flag
     * @return int|false
     */
    public function send(?string $data, int $length, int $flag)
    {
        return socket_send($this->socket, $data, $length, $flag);
    }

    /**
     * This will bind a name to the socket.
     *
     * @param int $backlog
     * @return bool
     */
    public function listen(int $backlog = 0): bool
    {
        return socket_listen($this->socket, $backlog);
    }

    /**
     * This will shutdown the socket.
     *
     * @param int $mode
     * @return bool
     */
    public function shutDown(int $mode = 2): bool
    {
        return socket_shutdown($this->socket, $mode);
    }

    /**
     * This will accept a connection on the socket.
     *
     * @return SocketInterface
     */
    public function accept(): SocketInterface
    {
        $socket = socket_accept($this->socket);
        return new static($socket);
    }

    /**
     * This will close the socket.
     *
     * @return void
     */
    public function close(): void
    {
        socket_close($this->socket);
    }
}
