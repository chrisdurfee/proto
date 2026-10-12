<?php declare(strict_types=1);
namespace Proto\Http;

use DateTimeImmutable;
use Exception;

/**
 * Class Jwt
 *
 * Handles JWT encoding and decoding securely.
 *
 * @package Proto\Http
 */
class Jwt
{
	/**
	 * Creates a new JWT encoded token.
	 *
	 * @param array $payload Payload data.
	 * @param string $secret Secret key for signing.
	 * @param array|null $header Optional JWT header.
	 * @param string $expires Expiration time modifier (default: +1 minute).
	 * @return string JWT token.
	 */
	public static function encode(
		array $payload,
		string $secret,
		?array $header = null,
		string $expires = '+1 minute'
	): string
	{
		// Tokens are always signed with HMAC-SHA256; never advertise another alg.
		$header = self::encodeJson(array_merge($header ?? ['typ' => 'JWT'], ['alg' => 'HS256']));
		$payload = self::encodeJson(array_merge(self::getPayloadDefaults($expires), $payload));
		$signature = self::generateSignature($secret, $header, $payload);

		return "{$header}.{$payload}.{$signature}";
	}

	/**
	 * Generates default payload claims.
	 *
	 * @param string $expires Expiration time modifier.
	 * @return array Default payload claims.
	 */
	protected static function getPayloadDefaults(string $expires): array
	{
		$issuedAt = new DateTimeImmutable();

		return [
			'iat' => $issuedAt->getTimestamp(),
			'exp' => $issuedAt->modify($expires)->getTimestamp(),
		];
	}

	/**
	 * Generates the JWT signature.
	 *
	 * @param string $secret Secret key for signing.
	 * @param string $header Encoded header.
	 * @param string $payload Encoded payload.
	 * @return string Encoded signature.
	 */
	protected static function generateSignature(
		string $secret,
		string $header,
		string $payload
	): string
	{
		$signature = hash_hmac('sha256', "{$header}.{$payload}", $secret, true);
		return self::base64UrlEncode($signature);
	}

	/**
	 * Decodes a JWT token and validates its integrity.
	 *
	 * Rejects (returns null) a token that is malformed, not HS256, badly
	 * signed, expired, not yet valid (`nbf`), or, unless `$requireExp` is
	 * false, has no `exp` claim (it would never expire).
	 *
	 * @param string $token JWT token.
	 * @param string $secret Secret key for verification.
	 * @param bool $requireExp Whether a token without `exp` is rejected.
	 * @return array|null Decoded payload if valid, otherwise null.
	 */
	public static function decode(string $token, string $secret, bool $requireExp = true): ?array
	{
		$parts = explode('.', $token);
		if (count($parts) !== 3)
		{
			return null;
		}

		[$header, $payload, $signature] = $parts;
		$calculatedSignature = self::generateSignature($secret, $header, $payload);

		if (!hash_equals($calculatedSignature, $signature))
		{
			return null;
		}

		$decodedHeader = json_decode(self::base64UrlDecode($header), true);
		if (!is_array($decodedHeader) || ($decodedHeader['alg'] ?? null) !== 'HS256')
		{
			return null;
		}

		$decodedPayload = json_decode(self::base64UrlDecode($payload), true);
		if (!is_array($decodedPayload))
		{
			return null;
		}

		if ($requireExp && !isset($decodedPayload['exp']))
		{
			return null;
		}

		if (self::isTokenExpired($decodedPayload) || self::isNotYetValid($decodedPayload))
		{
			return null;
		}

		return $decodedPayload;
	}

	/**
	 * Checks the `nbf` (not before) claim.
	 *
	 * @param array $payload Decoded payload.
	 * @return bool True if the token is not valid yet.
	 */
	protected static function isNotYetValid(array $payload): bool
	{
		return isset($payload['nbf']) && is_numeric($payload['nbf'])
			&& (int)$payload['nbf'] > (new DateTimeImmutable())->getTimestamp();
	}

	/**
	 * Checks if the token has expired.
	 *
	 * @param array $payload Decoded payload.
	 * @return bool True if expired, false otherwise.
	 */
	protected static function isTokenExpired(array $payload): bool
	{
		$currentTime = (new DateTimeImmutable())->getTimestamp();
		return isset($payload['exp']) && (!is_numeric($payload['exp']) || (int)$payload['exp'] < $currentTime);
	}

	/**
	 * Encodes data into a base64 URL-safe format.
	 *
	 * @param string $data Data to encode.
	 * @return string URL-safe base64 encoded string.
	 */
	protected static function base64UrlEncode(string $data): string
	{
		return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
	}

	/**
	 * Decodes a base64 URL-safe encoded string.
	 *
	 * @param string $data Encoded data.
	 * @return string Decoded string.
	 */
	protected static function base64UrlDecode(string $data): string
	{
		$data = str_replace(['-', '_'], ['+', '/'], $data);
		return base64_decode($data);
	}

	/**
	 * Encodes an array to a JSON string and then base64 encodes it.
	 *
	 * @param mixed $data Data to encode.
	 * @return string Base64 encoded JSON string.
	 * @throws Exception If JSON encoding fails.
	 */
	protected static function encodeJson(mixed $data): string
	{
		$json = json_encode($data, JSON_THROW_ON_ERROR);
		return self::base64UrlEncode($json);
	}
}