<?php

include __DIR__ . '../../../../autoload.php';

use Proto\Http\Socket\Server;
use Proto\Http\Socket\Connection;

/**
 * Run the server.
 *
 * php ./proto/http/socket/example/example-socket-server.php
 */

/**
 * This create a socket on the address and port.
 */
$ADDRESS = '127.0.0.1';
$PORT = 8080;

$server = new Server($ADDRESS, $PORT);

/**
 * This can secure the socket.
 */
//$server->secure();

/**
 * This will start a server event and add a
 * message event listener.
 */
$server->on('connection', function(Connection $connection)
{
    /**
     * This will write a message to the socket.
     */
    $connection->write('The socket is started');
    echo "data : The socket is started \n\n";

    /**
     * This will listen for error events.
     */
    $connection->on('error', function(mixed $data) use ($connection)
    {
        $connection->write(json_encode($data));
    });

    /**
     * This will listen for data events.
     */
    $connection->on('data', function(mixed $data) use ($connection)
    {
        $connection->write(json_encode($data));
    });

    /**
     * This will listen for close events.
     */
    $connection->on('close', function()
    {

    });
});

$server->run();

/**
 * This will sto pthe server.
 */
//$server->stop();