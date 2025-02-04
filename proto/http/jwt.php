<?php declare(strict_types=1);
namespace Proto\Http;

use DateTimeImmutable;

/**
 * Class Jwt
 *
 * Creates and retrieves JWT tokens.
 *
 * @package Proto\Http
 */
class Jwt
{
	/**
	 * Creates a new JWT encoded token.
	 *
	 * @param array $payload
	 * @param string $secret
	 * @param array|null $header
	 * @param string $expires
	 * @return string
	 */
	public static function encode(
		array $payload,
		string $secret,
		?array $header = null,
		string $expires = '+1 minute'
	): string
	{
		$header = self::getHeader($header);
		$payload = self::getPayload($payload, $expires);
		$signature = self::getSignature($secret, $header, $payload);

		return $header . "." . $payload . "." . $signature;
	}

	/**
	 * Encodes a header if passed or uses a default header.
	 *
	 * @param array|null $header
	 * @return string
	 */
	protected static function getHeader(?array $header = null): string
	{
		$header = $header ?? ['typ' => 'JWT', 'alg' => 'HS256'];
		return self::_encode($header);
	}

	/**
	 * Gets the payload and augments it with the default payload data.
	 *
	 * @param array $payload
	 * @param string $expires
	 * @return string
	 */
	protected static function getPayload(array $payload, string $expires): string
	{
		$defaults = self::getPayloadDefaults($expires);
		return self::_encode(array_merge($defaults, $payload));
	}

	/**
	 * Gets the payload defaults.
	 *
	 * @param string $expires
	 * @return array
	 */
	protected static function getPayloadDefaults(string $expires): array
	{
		$issuedAt = new DateTimeImmutable();
		return [
			"iat" => $issuedAt->getTimestamp(),
			"exp" => $issuedAt->modify($expires)->getTimestamp()
		];
	}

	/**
	 * Gets the signature.
	 *
	 * @param string $secret
	 * @param string $header
	 * @param string $payload
	 * @return string
	 */
	protected static function getSignature(
		string $secret,
		string $header,
		string $payload
	): string
	{
		$signature = hash_hmac('sha256', $header . "." . $payload, $secret, true);
		return self::base64Replace($signature);
	}

	/**
	 * Converts the string to base64 characters.
	 *
	 * @param string $str
	 * @return string
	 */
	protected static function base64Replace(string $str): string
	{
		return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($str));
	}

	/**
	 * Encodes the data.
	 *
	 * @param mixed $value
	 * @return string
	 */
	protected static function _encode($value): string
	{
		$value = json_encode($value);
		return self::base64Replace($value);
	}

	/**
	 * Decodes a JWT token and returns the payload.
	 *
	 * @param string $token
	 * @param string $secret
	 * @return array|null
	 */
	public static function decode(string $token, string $secret): ?array
	{
		[$header, $payload, $signature] = explode('.', $token);

		$calculatedSignature = self::getSignature($secret, $header, $payload);

		if ($signature !== $calculatedSignature)
		{
			return null;
		}

		$payloadData = json_decode(self::base64Decode($payload), true);

		if (!self::isTokenExpired($payloadData))
		{
			return $payloadData;
		}

		return null;
	}

	/**
	 * Converts the base64 characters back to the original string.
	 *
	 * @param string $str
	 * @return string
	 */
	protected static function base64Decode(string $str): string
	{
		$str = str_replace(['-', '_'], ['+', '/'], $str);
		return base64_decode($str);
	}

	/**
	 * Checks if the token is expired.
	 *
	 * @param array $payload
	 * @return bool
	 */
	protected static function isTokenExpired(array $payload): bool
	{
		$currentTime = (new DateTimeImmutable())->getTimestamp();
		return isset($payload['exp']) && $payload['exp'] < $currentTime;
	}
}