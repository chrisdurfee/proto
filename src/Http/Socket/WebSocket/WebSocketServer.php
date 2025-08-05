<?php declare(strict_types=1);
namespace Proto\Http\Socket\WebSocket;

use Proto\Http\Socket\Server;
use Proto\Http\Socket\SocketInterface;

/**
 * Class WebSocketServer
 *
 * Manages WebSocket connections and upgrades them from standard socket connections.
 *
 * @package Proto\Http\Socket\WebSocket
 */
class WebSocketServer extends Server
{
	/**
	 * Creates and upgrades a WebSocket connection.
	 *
	 * @param SocketInterface $socket The raw socket interface.
	 * @return Connection The upgraded WebSocket connection.
	 */
	protected function createConnection(SocketInterface $socket): Connection
	{
		$connection = new Connection($socket);

		// Attempt to upgrade the connection to WebSocket
		$connection->upgrade();
		return $connection;
	}
}