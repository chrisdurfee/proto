<?php declare(strict_types=1);
namespace Proto\Http;

/**
 * RateLimiterIdentity
 *
 * Shared identity resolver for rate limiters: prefer the signed-in user
 * id, otherwise fall back to client IP. Feature-specific buckets stay in
 * the app; this only normalizes the identity segment.
 *
 * @package Proto\Http
 */
class RateLimiterIdentity
{
	/**
	 * Resolve `user:{id}` or `ip:{address}` for rate-limit keys.
	 *
	 * @return string
	 */
	public static function resolve(): string
	{
		$userId = 0;
		if (function_exists('session'))
		{
			$userId = (int)(session()->user->id ?? 0);
		}

		if ($userId > 0)
		{
			return 'user:' . $userId;
		}

		return 'ip:' . (Request::ip() ?? 'unknown');
	}
}
