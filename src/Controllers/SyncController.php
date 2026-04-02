<?php declare(strict_types=1);
namespace Proto\Controllers;

use Proto\Controllers\Traits\SyncableTrait;

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
	use SyncableTrait;
}
