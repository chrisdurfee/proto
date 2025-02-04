<?php declare(strict_types=1);
namespace Proto\Http\Socket\WebSocket;

/**
 * Headers
 *
 * This will get the socket headers.
 *
 * @package Proto\Http\Socket\WebSocket
 */
class Headers
{
    /**
     * This will get the socket key.
     *
     * @param string $request
     * @return string|null
     */
    private static function getSocketKey(string $request): ?string
    {
        preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $request, $matches);
        return $matches[1] ?? null;
    }

    /**
     * This will encode the socket key.
     *
     * @param string $key
     * @return string
     */
    private static function encodeSockeyKey(string $key): string
    {
        return base64_encode(pack(
            'H*',
            sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')
        ));
    }

    /**
     * This will get the socket headers.
     *
     * @param string $request
     * @return string|null
     */
	public static function get(string $request): ?string
    {
        $key = self::getSocketKey($request);
        if (!isset($key))
        {
            return null;
        }

        $encodedKey = static::encodeSockeyKey($key);
        $headers = "HTTP/1.1 101 Switching Protocols\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "Connection: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Version: 13\r\n";
        $headers .= "Sec-WebSocket-Accept: {$encodedKey}\r\n\r\n";
        return $headers;
    }
}