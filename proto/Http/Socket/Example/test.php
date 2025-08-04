<?php

include __DIR__ . '../../../../vendor/autoload.php';

use Proto\Http\Socket\Server;
use Proto\Http\Socket\Connection;

/**
 * Run the server.
 *
 * php ./proto/http/socket/example/test.php
 */

/**
 * This create a socket on the address and port.
 */
$ADDRESS = '127.0.0.1';
$PORT = 8080;

$server = new Server($ADDRESS, $PORT);
echo "data: Server started at {$ADDRESS}:{$PORT}\n\n";

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
        $connection->write("data: Error " . json_encode($data) . "\n\n");
    });

    /**
     * This will listen for data events.
     */
    $connection->on('data', function(mixed $data) use ($connection)
    {
        $connection->write("data: " . json_encode($data) . "\n\n");
    });

    /**
     * This will listen for close events.
     */
    $connection->on('close', function()
    {
        echo "data: The socket is closed \n\n";
    });
});

$server->run();

/**
 * This will sto pthe server.
 */
//$server->stop();