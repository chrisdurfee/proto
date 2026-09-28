<?php declare(strict_types=1);
namespace Proto\Auth\Gates;

use Proto\Http\Cookie;
use Proto\Http\Request;

/**
 * CrossSiteRequestForgeryGate
 *
 * Session-bound CSRF gate with a readable double-submit cookie for SPAs.
 *
 * The token lives in the session (source of truth). A non-HttpOnly
 * `XSRF-TOKEN` cookie mirrors it so client JS can copy the value into
 * the `X-XSRF-TOKEN` header on mutations. Validation always checks the
 * header against the session token, never cookie-equals-header alone.
 *
 * @package Proto\Auth\Gates
 */
class CrossSiteRequestForgeryGate extends Gate
{
	/**
	 * Session key and legacy request header name.
	 */
	const CSRF_TOKEN = 'csrf-token';

	/**
	 * Readable cookie that delivers the token to SPA JavaScript.
	 */
	const COOKIE_NAME = 'XSRF-TOKEN';

	/**
	 * Preferred mutation header (lowercase for Request::header()).
	 */
	const HEADER_NAME = 'x-xsrf-token';

	/**
	 * Token length in bytes before hex encoding.
	 */
	const TOKEN_LENGTH = 128;

	/**
	 * This will create the token.
	 *
	 * @return string
	 */
	protected function createToken(): string
	{
		return bin2hex(random_bytes(self::TOKEN_LENGTH));
	}

	/**
	 * Ensure a session CSRF token exists and sync the readable cookie.
	 *
	 * Calling this on an existing token still refreshes the cookie so a
	 * boot GET can mint `XSRF-TOKEN` without rotating the session value.
	 *
	 * @return string
	 */
	public function setToken(): string
	{
		$token = $this->getToken();
		if (!isset($token))
		{
			$token = $this->createToken();
			$this->set(self::CSRF_TOKEN, $token);
		}

		$this->syncCookie($token);
		return $token;
	}

	/**
	 * This will get the request token.
	 *
	 * @return string|null
	 */
	public function getToken(): ?string
	{
		return $this->get(self::CSRF_TOKEN);
	}

	/**
	 * This will reset the token.
	 *
	 * Call on login, logout, or privilege escalation to rotate
	 * the CSRF token per OWASP token rotation guidelines.
	 *
	 * @return void
	 */
	public function reset(): void
	{
		$this->set(self::CSRF_TOKEN, null);
		$this->clearCookie();
	}

	/**
	 * Rotates the CSRF token by resetting and generating a new one.
	 *
	 * @return string The new token.
	 */
	public function rotate(): string
	{
		$this->reset();
		return $this->setToken();
	}

	/**
	 * This will check if the token is valid.
	 *
	 * Accepts `X-XSRF-TOKEN` (preferred) or the legacy `csrf-token` header.
	 * Both are checked against the session token.
	 *
	 * @return bool
	 */
	public function isValid(): bool
	{
		$csrfHeader = $this->readRequestToken();
		if ($csrfHeader === null || $csrfHeader === '')
		{
			return false;
		}

		return $this->validateToken($csrfHeader);
	}

	/**
	 * This will validate the token.
	 *
	 * @param string $token
	 * @return bool
	 */
	public function validateToken(string $token): bool
	{
		$storedToken = $this->get(self::CSRF_TOKEN);
		if (empty($storedToken))
		{
			return false;
		}

		return hash_equals($storedToken, $token);
	}

	/**
	 * Read the CSRF token from the preferred or legacy request header.
	 *
	 * @return string|null
	 */
	protected function readRequestToken(): ?string
	{
		foreach ([self::HEADER_NAME, self::CSRF_TOKEN] as $header)
		{
			$value = Request::header($header);
			if (!empty($value))
			{
				return $value;
			}
		}

		return null;
	}

	/**
	 * Mirror the session token into a non-HttpOnly cookie for SPA clients.
	 *
	 * @param string $token
	 * @return void
	 */
	protected function syncCookie(string $token): void
	{
		(new Cookie(self::COOKIE_NAME, $token, $this->cookieExpiresAt(), false))->set();
	}

	/**
	 * Remove the readable XSRF cookie (e.g. on logout / rotate).
	 *
	 * @return void
	 */
	protected function clearCookie(): void
	{
		Cookie::remove(self::COOKIE_NAME, false);
	}

	/**
	 * Cookie expiry aligned with session idle lifetime when configured.
	 *
	 * @return int Unix timestamp, or 0 for a session cookie.
	 */
	protected function cookieExpiresAt(): int
	{
		$lifetime = (int)(env('sessionLifetime') ?? 0);
		return $lifetime > 0 ? time() + $lifetime : 0;
	}
}
