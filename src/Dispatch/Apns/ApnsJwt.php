<?php declare(strict_types=1);
namespace Proto\Dispatch\Apns;

/**
 * ApnsJwt
 *
 * Builds and caches the ES256 provider token used to authenticate
 * with Apple's APNs HTTP/2 API. Apple requires the token to be
 * refreshed between 20 and 60 minutes; a 45 minute cache keeps every
 * request inside that window.
 *
 * @package Proto\Dispatch\Apns
 */
class ApnsJwt
{
	/**
	 * Cached provider token.
	 *
	 * @var string|null
	 */
	protected static ?string $token = null;

	/**
	 * Unix timestamp the cached token was issued at.
	 *
	 * @var int
	 */
	protected static int $issuedAt = 0;

	/**
	 * Cache lifetime in seconds (45 minutes).
	 *
	 * @var int
	 */
	protected const TOKEN_TTL = 2700;

	/**
	 * Creates (or reuses) an ES256 signed provider token.
	 *
	 * @param string $keyId The APNs auth key ID.
	 * @param string $teamId The Apple developer team ID.
	 * @param string $keyPath Path to the .p8 private key file.
	 * @return string|null The JWT, or null on failure.
	 */
	public static function create(string $keyId, string $teamId, string $keyPath): ?string
	{
		if (self::$token !== null && (time() - self::$issuedAt) < self::TOKEN_TTL)
		{
			return self::$token;
		}

		if (!is_readable($keyPath))
		{
			error_log('[Apns] private key is not readable: ' . $keyPath);
			return null;
		}

		$key = openssl_pkey_get_private((string)file_get_contents($keyPath));
		if ($key === false)
		{
			error_log('[Apns] failed to parse the .p8 private key.');
			return null;
		}

		$header = self::encode((string)json_encode(['alg' => 'ES256', 'kid' => $keyId]));
		$claims = self::encode((string)json_encode(['iss' => $teamId, 'iat' => time()]));
		$message = $header . '.' . $claims;

		$signature = '';
		if (!openssl_sign($message, $signature, $key, OPENSSL_ALGO_SHA256))
		{
			error_log('[Apns] failed to sign the provider token.');
			return null;
		}

		$raw = self::derToRaw($signature);
		if ($raw === null)
		{
			error_log('[Apns] failed to convert the DER signature.');
			return null;
		}

		self::$issuedAt = time();
		self::$token = $message . '.' . self::encode($raw);
		return self::$token;
	}

	/**
	 * Clears the cached provider token (useful in tests).
	 *
	 * @return void
	 */
	public static function clearCache(): void
	{
		self::$token = null;
		self::$issuedAt = 0;
	}

	/**
	 * Base64url encodes a string.
	 *
	 * @param string $value The value to encode.
	 * @return string The encoded value.
	 */
	protected static function encode(string $value): string
	{
		return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
	}

	/**
	 * Converts an ASN.1 DER ECDSA signature to the raw 64-byte
	 * r||s form required by JWS ES256.
	 *
	 * @param string $der The DER encoded signature.
	 * @return string|null The raw signature, or null if malformed.
	 */
	protected static function derToRaw(string $der): ?string
	{
		$pos = 0;
		if (ord($der[$pos++]) !== 0x30)
		{
			return null;
		}

		$length = ord($der[$pos++]);
		if ($length & 0x80)
		{
			$pos += ($length & 0x7F);
		}

		$components = [];
		for ($i = 0; $i < 2; $i++)
		{
			if (!isset($der[$pos]) || ord($der[$pos++]) !== 0x02)
			{
				return null;
			}

			$intLength = ord($der[$pos++]);
			$integer = substr($der, $pos, $intLength);
			$pos += $intLength;

			$integer = ltrim($integer, "\x00");
			if (strlen($integer) > 32)
			{
				return null;
			}

			$components[] = str_pad($integer, 32, "\x00", STR_PAD_LEFT);
		}

		return $components[0] . $components[1];
	}
}
