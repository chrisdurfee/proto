<?php declare(strict_types=1);

namespace Proto\Utils\Format;

/**
 * Format Abstract Class
 *
 * Defines an interface for data encoding and decoding.
 *
 * @package Proto\Utils\Format
 */
abstract class Format
{
	/**
	 * Encodes data into a specific format.
	 *
	 * @param mixed $data The data to encode
	 * @return string|null Encoded data as a string or null on failure
	 */
	abstract public static function encode(mixed $data): ?string;

	/**
	 * Decodes data from a specific format.
	 *
	 * @param mixed $data The data to decode
	 * @return mixed Decoded data or null on failure
	 */
	abstract public static function decode(mixed $data): mixed;
}