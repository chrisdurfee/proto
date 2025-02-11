<?php declare(strict_types=1);
namespace Proto\Models\Data;

use Proto\Utils\Strings;

/**
 * Class Mapper
 *
 * Default property mapper using camelCase conversion.
 *
 * @package Proto\Models
 */
class Mapper extends AbstractMapper
{
	/**
	 * Converts a string to camelCase.
	 *
	 * @param string $str Input string.
	 * @return string
	 */
	protected function convert(string $str): string
	{
		return Strings::camelCase($str);
	}

    /**
     * Factory method to create a new mapper.
     *
     * @param string $type Mapper type.
     * @return AbstractMapper
     */
    static function factory(string $type): AbstractMapper
    {
        switch ($type)
        {
            case 'snake':
                return new SnakeCaseMapper();
            default:
                return new Mapper();
        }
    }
}
