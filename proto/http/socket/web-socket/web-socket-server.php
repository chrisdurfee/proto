<?php declare(strict_types=1);
namespace Proto\Http\Socket\WebSocket;

use Proto\Http\Socket\Server;
use Proto\Http\Socket\SocketInterface;

/**
 * Server
 *
 * This class represents a server socket and provides methods to manage its connections.
 *
 * @package Proto\Http\Socket\WebSocket
 */
class WebSocketServer extends Server
{
    /**
	 * This will create a connection.
	 *
	 * @param SocketInterface $socket
	 * @return Connection
	 */
	protected function createConnection(SocketInterface $socket): Connection
	{
		$connection = new Connection($socket);

        /**
         * This will upgrade the connection to a WebSocket connection.
         */
        $connection->upgrade();
        return $connection;
	}
}