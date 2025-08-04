<?php declare(strict_types=1);
namespace Proto\Http\Socket\WebSocket;

/**
 * Class MessageHandler
 *
 * Handles encoding and decoding of WebSocket messages.
 *
 * @package Proto\Http\Socket\WebSocket
 */
class MessageHandler
{
	/**
	 * Unseals a WebSocket frame and extracts the payload data.
	 *
	 * @param string $socketData The received WebSocket frame.
	 * @return string The decoded message.
	 */
	public static function unseal(string $socketData): string
	{
		$length = ord($socketData[1]) & 127;
		if ($length === 126)
		{
			$masks = substr($socketData, 4, 4);
			$data = substr($socketData, 8);
		}
		elseif ($length === 127)
		{
			$masks = substr($socketData, 10, 4);
			$data = substr($socketData, 14);
		}
		else
		{
			$masks = substr($socketData, 2, 4);
			$data = substr($socketData, 6);
		}

		$dataLength = strlen($data);
		$unmaskedData = '';

		for ($i = 0; $i < $dataLength; ++$i)
		{
			$unmaskedData .= $data[$i] ^ $masks[$i % 4];
		}

		return $unmaskedData;
	}

	/**
	 * Seals a message into a WebSocket frame.
	 *
	 * @param string $socketData The message to be framed.
	 * @return string The framed message ready for transmission.
	 */
	public static function seal(string $socketData): string
	{
		$b1 = 0x81; // FIN bit set + text frame opcode
		$length = strlen($socketData);
		if ($length <= 125)
		{
			$header = pack('CC', $b1, $length);
		}
		elseif ($length <= 65535)
		{
			$header = pack('CCn', $b1, 126, $length);
		}
		else
		{
			$header = pack('CCJ', $b1, 127, $length); // Uses 64-bit length
		}

		return $header . $socketData;
	}
}