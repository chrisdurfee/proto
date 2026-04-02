<?php declare(strict_types=1);
namespace Proto\Controllers;

use Proto\Http\Router\Request;

/**
 * SyncController
 *
 * Base controller for Server-Sent Events (SSE) endpoints that use Redis pub/sub.
 *
 * Subclasses define a channel name and a message handler. This base class
 * eliminates the repeated SSE/Redis setup boilerplate across sync endpoints.
 *
 * Usage:
 * ```php
 * class PostSyncController extends SyncController
 * {
 *     protected function getChannel(Request $request): string
 *     {
 *         $postId = $request->getInt('postId');
 *         return "post:{$postId}";
 *     }
 *
 *     protected function handleMessage(string $channel, array $message, Request $request): ?array
 *     {
 *         return ['merge' => $message, 'deleted' => []];
 *     }
 * }
 * ```
 *
 * @package Proto\Controllers
 * @abstract
 */
abstract class SyncController extends ApiController
{
	/**
	 * Start the SSE stream.
	 *
	 * Sets up the Redis subscription on the channel returned by getChannel()
	 * and dispatches messages through handleMessage().
	 *
	 * @param Request $request The incoming request.
	 * @return void
	 */
	public function sync(Request $request): void
	{
		$channel = $this->getChannel($request);
		redisEvent($channel, fn(string $ch, array $msg) => $this->handleMessage($ch, $msg, $request));
	}

	/**
	 * Get the Redis channel name(s) for this SSE endpoint.
	 *
	 * @param Request $request The incoming request (use to extract route params).
	 * @return string|array Single channel name or array of channel names.
	 */
	abstract protected function getChannel(Request $request): string|array;

	/**
	 * Handle an incoming Redis message.
	 *
	 * Return an associative array to send as an SSE event, or null to skip.
	 * Return false to terminate the connection.
	 *
	 * @param string $channel The channel the message was received on.
	 * @param array $message The decoded message payload.
	 * @param Request $request The original HTTP request.
	 * @return array|null|false
	 */
	abstract protected function handleMessage(string $channel, array $message, Request $request): array|null|false;
}
