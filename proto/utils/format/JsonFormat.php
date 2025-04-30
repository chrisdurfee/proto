<?php declare(strict_types=1);
namespace Proto\Utils\Format;

/**
 * JsonFormat
 *
 * Handles encoding and decoding of JSON data.
 *
 * @package Proto\Utils\Format
 */
class JsonFormat extends Format
{
	/**
	 * Normalizes a string by removing BOM and ensuring UTF-8 encoding.
	 *
	 * @param string $data
	 * @return string
	 */
	protected static function normalizeString(string $data): string
	{
		$data = trim($data);

		// Remove UTF-8 Byte Order Mark (BOM) if present
		if (str_starts_with(bin2hex($data), 'efbbbf'))
		{
			$data = substr($data, 3);
		}

		// Detect and convert encoding to UTF-8 if necessary
		$originalEncoding = mb_detect_encoding($data, mb_detect_order(), true);
		if ($originalEncoding && $originalEncoding !== 'UTF-8')
		{
			$data = mb_convert_encoding($data, 'UTF-8', $originalEncoding);
		}

		return $data;
	}

	/**
	 * Encodes data to JSON.
	 *
	 * @param mixed $data The data to encode.
	 * @return string|null JSON-encoded string or null on failure.
	 */
	public static function encode(mixed $data): ?string
	{
		if ($data === null)
		{
			return null;
		}

		$encodedData = json_encode(
			$data,
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE | JSON_INVALID_UTF8_SUBSTITUTE
		);

		if (json_last_error() !== JSON_ERROR_NONE)
		{
			self::logError("JSON Encode Error: " . json_last_error_msg(), $data);
			return null;
		}

		return $encodedData;
	}

	/**
	 * Encodes data to JSON and outputs it.
	 *
	 * @param mixed $data The data to encode and render.
	 * @return void
	 */
	public static function encodeAndRender(mixed $data): void
	{
		$encodedData = self::encode($data);
		echo $encodedData ?? 'Unable to encode the data to JSON.';
	}

	/**
	 * Decodes a JSON string.
	 *
	 * @param mixed $data The JSON-encoded string.
	 * @return mixed Decoded data or null on failure.
	 */
	public static function decode(mixed $data): mixed
	{
		if ($data === null || !is_string($data))
		{
			return null;
		}

		$decodedData = json_decode($data);

		if (json_last_error() !== JSON_ERROR_NONE)
		{
			self::logError("JSON Decode Error: " . json_last_error_msg(), $data);
			return null;
		}

		return $decodedData;
	}

	/**
	 * Logs JSON encoding/decoding errors.
	 *
	 * @param string $message Error message.
	 * @param mixed $data The data that caused the error.
	 * @return bool
	 */
	protected static function logError(string $message, mixed $data): bool
	{
		return error($message . ' data: ' . $data, __FILE__, __LINE__);
	}
}
