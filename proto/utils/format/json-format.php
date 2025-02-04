<?php declare(strict_types=1);
namespace Proto\Utils\Format;

/**
 * JsonFormat
 *
 * This will encode and decode data to and from json.
 *
 * @package Proto\Utils\Format
 */
class JsonFormat extends Format
{
	/**
	 * This will normalize the string.
	 *
	 * @param string $data
	 * @return string
	 */
	protected static function normalizeString(string $data): string
	{
		$data = trim($data);

		// Remove BOM if present
		if (0 === strpos(bin2hex($data), 'efbbbf'))
		{
			$data = substr($data, 3);
		}

		// Remove non printable characters
		//$data = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $data);

		// Convert to UTF-8 if necessary
		$originalEncoding = mb_detect_encoding($data, mb_detect_order(), true);
		if ($originalEncoding && $originalEncoding !== 'UTF-8')
		{
			$data = mb_convert_encoding($data, 'UTF-8', $originalEncoding);
		}

		return $data;
	}

	/**
	 * This will encode data.
	 *
	 * @param mixed $data
	 * @param bool $echo
	 * @return string|null
	 */
	public static function encode(mixed $data): ?string
	{
		$encodedData = null;
		if (!isset($data))
		{
			return $encodedData;
		}

		try
		{
			$encodedData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE | JSON_INVALID_UTF8_SUBSTITUTE);
			if (json_last_error() !== JSON_ERROR_NONE)
			{
				/**
				 * This will save the error to the log.
				 */
				self::error(
					json_last_error_msg() . "\n Error encoding data: " . (string)$data
				);
			}
		}
		catch (\Exception $e)
		{
			self::error(
				$e->getMessage() . "\n Error encoding data: " . (string)$data
			);
		}
		return $encodedData;
	}

	/**
	 * This will encode data and render to screen.
	 *
	 * @param mixed $data
	 * @param bool $echo
	 * @return void
	 */
	public static function encodeAndRender(mixed $data): void
	{
		$encodedData = static::encode($data);
		if (isset($encodedData))
		{
			echo $encodedData;
			return;
		}

		echo 'Unable to encode the data to JSON.';
	}

	/**
	 * This will add an error to the log.
	 *
	 * @param string $message
	 * @return bool
	 */
	protected static function error(string $message): bool
	{
		return error($message, __FILE__, __LINE__);
	}

	/**
	 * This will decode data.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public static function decode(mixed $data): mixed
	{
		$decodedData = null;
		if (!isset($data))
		{
			return $decodedData;
		}

		try
		{
			$normalizedData = static::normalizeString($data);
			$decodedData = json_decode($normalizedData);
			if (json_last_error() !== JSON_ERROR_NONE)
			{
				/**
				 * This will save the error to the log.
				 */
				self::error(
					json_last_error_msg() . "\n Error decoding data: " . (string)$data
				);
				return null;
			}
		}
		catch (\Exception $e)
		{
			self::error(
				$e->getMessage() . "\n Error decoding data: " . (string)$data
			);
		}
		return $decodedData;
	}
}