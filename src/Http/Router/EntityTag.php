<?php declare(strict_types=1);
namespace Proto\Http\Router;

/**
 * EntityTag
 *
 * Generates and compares HTTP entity tags (ETags) so repeat requests for
 * unchanged data can be answered with a bodyless 304 instead of the full
 * payload.
 *
 * @package Proto\Http\Router
 */
class EntityTag
{
	/**
	 * Hash used to fingerprint a response body.
	 *
	 * An entity tag only needs to change when the bytes change, so a fast
	 * non-cryptographic hash is appropriate. xxh128 is ~an order of
	 * magnitude faster than md5 on large payloads while keeping the
	 * collision probability negligible at 128 bits.
	 *
	 * @var string
	 */
	protected const ALGORITHM = 'xxh128';

	/**
	 * Suffixes an intermediary may append to a tag it re-encoded.
	 *
	 * Apache's mod_deflate/mod_brotli rewrite the origin tag as
	 * `"hash-gzip"` when they compress the body. The client echoes that
	 * modified value back in If-None-Match, so comparing it byte-for-byte
	 * against the origin tag would never match and every revalidation
	 * would fall through to a full 200.
	 *
	 * @var array<int, string>
	 */
	protected const TRANSFORM_SUFFIXES = ['-gzip', '-br', '-deflate', '-zstd'];

	/**
	 * Fingerprints a response body as a strong entity tag.
	 *
	 * @param string $body The already-serialized response body.
	 * @return string The quoted entity tag.
	 */
	public static function generate(string $body): string
	{
		return '"' . hash(self::ALGORITHM, $body) . '"';
	}

	/**
	 * Determines whether a client's If-None-Match satisfies a tag.
	 *
	 * @param string $etag The tag for the response about to be sent.
	 * @param string|null $ifNoneMatch The raw If-None-Match header value.
	 * @return bool True when the client already holds this representation.
	 */
	public static function matches(string $etag, ?string $ifNoneMatch): bool
	{
		if ($ifNoneMatch === null)
		{
			return false;
		}

		$ifNoneMatch = trim($ifNoneMatch);
		if ($ifNoneMatch === '')
		{
			return false;
		}

		if ($ifNoneMatch === '*')
		{
			return true;
		}

		$target = self::normalize($etag);
		foreach (explode(',', $ifNoneMatch) as $candidate)
		{
			if (self::normalize($candidate) === $target)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Reduces a tag to a comparable form.
	 *
	 * Strips the weak validator prefix, the surrounding quotes, and any
	 * content-coding suffix added by a compressing intermediary, so the
	 * weak comparison required for If-None-Match succeeds.
	 *
	 * @param string $etag
	 * @return string
	 */
	protected static function normalize(string $etag): string
	{
		$etag = trim($etag);
		if (stripos($etag, 'W/') === 0)
		{
			$etag = substr($etag, 2);
		}

		$etag = trim($etag, '"');
		foreach (self::TRANSFORM_SUFFIXES as $suffix)
		{
			if (str_ends_with($etag, $suffix))
			{
				return substr($etag, 0, -strlen($suffix));
			}
		}

		return $etag;
	}

	/**
	 * Reads the If-None-Match header for the current request.
	 *
	 * Read straight from the server array rather than through
	 * `Input::server()` so the value survives `filter_input()` being
	 * unavailable off an SAPI request, and so quoting is preserved.
	 * CR/LF/NUL are stripped because the value is echoed into comparisons
	 * driven by client input.
	 *
	 * @return string|null
	 */
	public static function requestedTag(): ?string
	{
		$value = $_SERVER['HTTP_IF_NONE_MATCH'] ?? null;
		if (!is_string($value) || $value === '')
		{
			return null;
		}

		return str_replace(["\r", "\n", "\0"], '', $value);
	}
}
