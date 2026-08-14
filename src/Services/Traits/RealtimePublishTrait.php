<?php declare(strict_types=1);
namespace Proto\Services\Traits;

use Proto\Events\Events;

/**
 * RealtimePublishTrait
 *
 * Tiny wrapper around `Events::update("redis:...", $payload)` so the
 * Redis-prefix convention lives in one place instead of being typed
 * by hand across services (comments, bids, votes, messages, streams).
 *
 * Usage:
 *
 *   class PostCommentService extends Service
 *   {
 *       use RealtimePublishTrait;
 *
 *       public function add(...): ServiceResult
 *       {
 *           // ...
 *           $this->publishRealtime("post:{$postId}:commented", [
 *               'postId' => $postId,
 *               'commentId' => $commentId,
 *           ]);
 *       }
 *   }
 *
 * Why a trait rather than a global helper:
 *   - keeps the channel-naming surface area inside services
 *   - lets us swap the underlying transport without touching every call site
 *   - participates in test doubles when a service is mocked
 *
 * @package Proto\Services\Traits
 */
trait RealtimePublishTrait
{
	/**
	 * Publish a payload on the realtime bus. The `redis:` prefix is
	 * added automatically so callers only think in business-level
	 * channel names (`post:{$id}:commented`, etc.).
	 *
	 * @param string $channel Channel name without the `redis:` prefix.
	 * @param array<string, mixed> $payload Serializable event payload.
	 * @return void
	 */
	protected function publishRealtime(string $channel, array $payload): void
	{
		Events::update('redis:' . ltrim($channel, ':'), $payload);
	}
}
