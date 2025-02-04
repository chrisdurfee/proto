<?php declare(strict_types=1);
namespace Proto\Utils\Filter;

/**
 * Input
 *
 * This will handle the input.
 *
 * @package Proto\Utils\Filter
 */
class Input extends Filter
{
    /**
     * This will filter a string.
     *
     * @param int $input
     * @param string|null $key
     * @return string
     */
    protected static function filter($input, ?string $key): string
    {
        if (empty($key))
        {
            return '';
        }

        $value = \filter_input($input, $key, FILTER_UNSAFE_RAW);
        return $value ?? '';
    }

	/**
	 * This will sanitize a get input.
	 *
	 * @param string $ip
	 * @return string
	 */
    public static function get(?string $key): string
    {
        return self::filter(INPUT_GET, $key);
    }

    /**
	 * This will sanitize a post input.
	 *
	 * @param string $ip
	 * @return string
	 */
    public static function post(?string $key): string
    {
        return self::filter(INPUT_POST, $key);
    }

    /**
     * This will sanitize a cookie input.
     *
     * @param string|null $key
     * @return string
     */
    public static function cookie(?string $key): string
    {
        return self::filter(INPUT_COOKIE, $key);
    }

    /**
     * This will sanitize a server input.
     *
     * @param string|null $key
     * @return string
     */
    public static function server(?string $key): string
    {
        return self::filter(INPUT_SERVER, $key);
    }
}