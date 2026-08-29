<?php declare(strict_types=1);
namespace Proto\Http\Router;

/**
 * CacheDirective
 *
 * Builds the `Cache-Control` value for a response and reports what a cache
 * is allowed to do with it, so `Vary` can be widened when a response may be
 * reused without contacting the server.
 *
 * @package Proto\Http\Router
 */
final class CacheDirective
{
	/**
	 * @param string $value The rendered Cache-Control value.
	 * @param bool $reusable Whether a cache may serve this response without revalidating.
	 */
	private function __construct(
		private readonly string $value,
		private readonly bool $reusable = false
	)
	{
	}

	/**
	 * Forbids storing the response anywhere.
	 *
	 * Use for responses carrying credentials, tokens, or data that must
	 * never touch disk. Because the client cannot store the payload it can
	 * never revalidate it either, so these responses get no ETag and never
	 * produce a 304.
	 *
	 * @return self
	 */
	public static function noStore(): self
	{
		return new self('no-store, no-cache, must-revalidate, max-age=0');
	}

	/**
	 * Allows the client to keep a copy but requires revalidation before
	 * every reuse. This is the default.
	 *
	 * The payload is never served without asking the server first, so
	 * responses stay as fresh as `no-store` while letting an unchanged
	 * representation be confirmed with a bodyless 304. It is also what
	 * keeps a browser from handing one user's cached response to the next
	 * user of the same device.
	 *
	 * @return self
	 */
	public static function revalidate(): self
	{
		return new self('private, no-cache, max-age=0, must-revalidate');
	}

	/**
	 * Lets a single client reuse the response for `$maxAge` seconds without
	 * contacting the server.
	 *
	 * Only for responses whose staleness is genuinely acceptable for that
	 * window. The response is user-specific, so `Vary: Cookie` is added to
	 * keep a session change from reusing the previous user's copy.
	 *
	 * @param int $maxAge Seconds the client may reuse the response.
	 * @param int|null $staleWhileRevalidate Seconds a stale copy may be served while refreshing.
	 * @return self
	 */
	public static function privateFor(int $maxAge, ?int $staleWhileRevalidate = null): self
	{
		$parts = ['private', 'max-age=' . max(0, $maxAge)];
		if ($staleWhileRevalidate !== null)
		{
			$parts[] = 'stale-while-revalidate=' . max(0, $staleWhileRevalidate);
		}

		return new self(implode(', ', $parts), $maxAge > 0);
	}

	/**
	 * Lets shared caches (CDN, reverse proxy) store the response.
	 *
	 * Only for responses identical for every viewer. A response carrying
	 * per-user fields must never use this.
	 *
	 * @param int $maxAge Seconds a client may reuse the response.
	 * @param int|null $sharedMaxAge Seconds a shared cache may reuse it (defaults to $maxAge).
	 * @param int|null $staleWhileRevalidate Seconds a stale copy may be served while refreshing.
	 * @return self
	 */
	public static function publicFor(int $maxAge, ?int $sharedMaxAge = null, ?int $staleWhileRevalidate = null): self
	{
		$parts = ['public', 'max-age=' . max(0, $maxAge)];
		if ($sharedMaxAge !== null)
		{
			$parts[] = 's-maxage=' . max(0, $sharedMaxAge);
		}

		if ($staleWhileRevalidate !== null)
		{
			$parts[] = 'stale-while-revalidate=' . max(0, $staleWhileRevalidate);
		}

		return new self(implode(', ', $parts), $maxAge > 0 || ($sharedMaxAge ?? 0) > 0);
	}

	/**
	 * The rendered Cache-Control value.
	 *
	 * @return string
	 */
	public function value(): string
	{
		return $this->value;
	}

	/**
	 * Whether a cache may serve this response without revalidating it.
	 *
	 * @return bool
	 */
	public function isReusable(): bool
	{
		return $this->reusable;
	}

	/**
	 * Whether the client is permitted to store the response, and therefore
	 * whether sending a validator can ever pay off.
	 *
	 * @return bool
	 */
	public function isStorable(): bool
	{
		return !str_contains($this->value, 'no-store');
	}

	/**
	 * @return string
	 */
	public function __toString(): string
	{
		return $this->value;
	}
}
