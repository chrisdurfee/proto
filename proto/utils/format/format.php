<?php declare(strict_types=1);
namespace Proto\Utils\Format;

use Proto\Utils\Util;

/**
 * Format
 *
 * This will handle the format.
 *
 * @package Proto\Utils\Format
 * @abstract
 */
abstract class Format extends Util
{
	/**
	 * This will encode data.
	 *
	 * @param mixed $data
	 * @return string|null
	 */
	abstract public static function encode($data): ?string;

	/**
	 * This will decode data.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	abstract public static function decode($data): mixed;
}