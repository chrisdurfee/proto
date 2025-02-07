<?php declare(strict_types=1);
namespace Proto\Http\Socket\WebSocket;

/**
 * Class Headers
 *
 * Handles WebSocket handshake headers.
 *
 * @package Proto\Http\Socket\WebSocket
 */
class Headers
{
	/**
	 * Extracts the Sec-WebSocket-Key from the request headers.
	 *
	 * @param string $request The raw HTTP request headers.
	 * @return string|null The extracted WebSocket key or null if not found.
	 */
	private static function getSocketKey(string $request): ?string
	{
		preg_match('#Sec-WebSocket-Key:\s*(\S+)#', $request, $matches);
		return isset($matches[1]) ? trim($matches[1]) : null;
	}

	/**
	 * Encodes the WebSocket key according to the WebSocket protocol.
	 *
	 * @param string $key The extracted WebSocket key.
	 * @return string The encoded WebSocket accept key.
	 */
	private static function encodeSocketKey(string $key): string
	{
		return base64_encode(pack(
			'H*',
			sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')
		));
	}

	/**
	 * Generates the WebSocket handshake response headers.
	 *
	 * @param string $request The raw HTTP request headers.
	 * @return string|null The formatted WebSocket handshake headers or null if the key is missing.
	 */
	public static function get(string $request): ?string
	{
		$key = self::getSocketKey($request);
		if ($key === null)
		{
			return null;
		}

		$encodedKey = self::encodeSocketKey($key);

		return sprintf(
			"HTTP/1.1 101 Switching Protocols\r\n".
			"Upgrade: websocket\r\n".
			"Connection: Upgrade\r\n".
			"Sec-WebSocket-Version: 13\r\n".
			"Sec-WebSocket-Accept: %s\r\n\r\n",
			$encodedKey
		);
	}
}