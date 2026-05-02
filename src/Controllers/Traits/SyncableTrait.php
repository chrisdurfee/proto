<?php declare(strict_types=1);
namespace Proto\Controllers\Traits;

use Proto\Http\Router\Request;
use Proto\Http\ServerEvents\SseConfig;

/**
 * SyncableTrait
 *
 * Adds SSE/Redis pub/sub sync support to any controller, including
 * ResourceControllers that already handle CRUD.
 *
 * Usage:
 * ```php
 * class NotificationController extends ResourceController
 * {
 *     use SyncableTrait;
 *
 *     protected function getSyncChannel(Request $request): string
 *     {
 *         return "user:" . session()->user->id . ":notifications";
 *     }
 *
 *     protected function handleSyncMessage(string $channel, array $message, Request $request): ?array
 *     {
 *         return ['merge' => $message, 'deleted' => []];
 *     }
 * }
 * ```
 *
 * @package Proto\Controllers\Traits
 */
trait SyncableTrait
{
	/**
	 * SSE sync endpoint using Redis pub/sub.
	 *
	 * Sets up the Redis subscription on the channel(s) returned by
	 * getSyncChannel() and dispatches messages through handleSyncMessage().
	 *
	 * @param Request $request The incoming request.
	 * @return void
	 */
	public function sync(Request $request): void
	{
		$channel = $this->getSyncChannel($request);
		redisEvent(
			$channel,
			fn(string $ch, array $msg) => $this->handleSyncMessage($ch, $msg, $request),
			$this->getSyncConfig($request)
		);
	}

	/**
	 * Optional per-endpoint SSE config overrides. Override in controllers
	 * that need a longer-lived stream, faster heartbeats, etc. Returning
	 * null uses framework defaults (see `SseConfig`).
	 *
	 * Example:
	 * ```php
	 * protected function getSyncConfig(Request $request): array
	 * {
	 *     return ['maxDuration' => 600, 'heartbeatInterval' => 10];
	 * }
	 * ```
	 *
	 * @param Request $request
	 * @return array<string, int>|SseConfig|null
	 */
	protected function getSyncConfig(Request $request): array|SseConfig|null
	{
		return null;
	}

	/**
	 * Get the Redis channel name(s) for this controller's sync endpoint.
	 *
	 * @param Request $request The incoming request (use to extract route params).
	 * @return string|array Single channel name or array of channel names.
	 */
	abstract protected function getSyncChannel(Request $request): string|array;

	/**
	 * Handle an incoming Redis sync message.
	 *
	 * Return an associative array to send as an SSE event, or null to skip.
	 * Return false to terminate the connection.
	 *
	 * @param string $channel The channel the message was received on.
	 * @param array $message The decoded message payload.
	 * @param Request $request The original HTTP request.
	 * @return array|null|false
	 */
	abstract protected function handleSyncMessage(string $channel, array $message, Request $request): array|null|false;
}
