<?php declare(strict_types=1);
namespace Proto\Models\Data;

use Proto\Utils\Strings;

/**
 * Class SnakeCaseMapper
 *
 * Mapper that converts keys to snake_case.
 *
 * @package Proto\Models\Data
 */
class SnakeCaseMapper extends AbstractMapper
{
	/**
	 * Converts a string to snake_case.
	 *
	 * @param string $str Input string.
	 * @return string
	 */
	protected function convert(string $str): string
	{
		return Strings::snakeCase($str);
	}
}