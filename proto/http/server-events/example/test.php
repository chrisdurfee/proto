<?php

include __DIR__ . '../../../autoload.php';

use Proto\Http\Loop\FiberEvent;
use Proto\Http\ServerEvents\ServerEvents;

/**
 * This will set up the interval in seconds.
 */
$INTERVAL_IN_SECONDS = 20;
$server = new ServerEvents($INTERVAL_IN_SECONDS);

/**
 * This will start a server event and add a
 * message event listener.
 */
$server->start(function($loop)
{
	$loop->addEvent(new FiberEvent(function(FiberEvent $event)
	{
		/**
		 * Perform any operation on the server and get the response.
		 */
		$response = true;

		/**
		 * The call back will no send any updates if null is returned.
		 */
		if (!$response)
		{
			return null;
		}

		/**
		 * This will send the response to the client.
		 */
		return $response;
	}));
});

/**
 * or you can stream a single response.
 */
$server->stream(function(FiberEvent $event)
{
	// Perform any operation on the server and get the response.
	return (object) [
		'message' => 'Hello World!'
	];
});