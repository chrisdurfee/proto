<?php declare(strict_types=1);
namespace Proto\Utils\Filter;

/**
 * Validate
 *
 * This will handle the validate.
 *
 * @package Proto\Utils\Filter
 */
class Validate extends Filter
{
    /**
     * This will validate an int.
     *
     * @param int $int
     * @return bool
     */
    public static function int($int): bool
    {
        return static::filter($int, FILTER_VALIDATE_INT);
    }

    /**
     * This will validate a float.
     *
     * @param float $number
     * @return bool
     */
    public static function float($number): bool
    {
        return static::filter($number, FILTER_VALIDATE_FLOAT);
    }

	/**
	 * This will validate an ip.
	 *
	 * @param string $ip
	 * @return bool
	 */
    public static function ip(?string $ip): bool
    {
        return static::filter($ip, FILTER_VALIDATE_IP);
    }

    /**
	 * This will validate a mac address.
	 *
	 * @param string $mac
	 * @return bool
	 */
    public static function mac(?string $mac): bool
    {
        return static::filter($mac, FILTER_VALIDATE_MAC);
    }

    /**
	 * This will validate an email.
	 *
	 * @param string $email
	 * @return bool
	 */
    public static function email(?string $email): bool
    {
        return static::filter($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * This will validate a string.
     *
     * @param string|null $string
     * @return bool
     */
    public static function string($string = null): bool
    {
        if (empty($string))
        {
            return false;
        }

        return \is_string($string);
    }

    /**
     * This will validate a phone.
     *
     * @param string $phone
     * @return bool
     */
    public static function phone($phone = null): bool
    {
        if (empty($phone))
        {
            return false;
        }

        $phone = \preg_replace('/[^0-9]/', '', $phone);
        return (\strlen($phone) === 10);
    }

    /**
	 * This will validate a URL.
	 *
	 * @param string $url
	 * @return bool
	 */
    public static function url(?string $url): bool
    {
        return static::filter($url, FILTER_VALIDATE_URL);
    }

    /**
     * This will validate a bool.
     *
     * @param string|bool $url
     * @return bool
     */
    public static function bool($bool): bool
    {
        return static::filter($bool, FILTER_VALIDATE_BOOL);
    }

    /**
	 * This will validate a domain.
	 *
	 * @param string $domain
	 * @return bool
	 */
    public static function domain(?string $domain): bool
    {
        return static::filter($domain, FILTER_VALIDATE_DOMAIN);
    }

    /**
     * This will validate the key by flag.
     *
     * @param mixed $key
     * @param mixed $flag
     * @return bool
     */
    protected static function filter($key, $flag): bool
    {
        if (\is_null($key))
        {
            return false;
        }

        return (\filter_var($key, $flag) !== false);
    }
}
