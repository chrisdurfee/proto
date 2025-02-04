<?php declare(strict_types=1);
namespace Proto\Utils\Filter;

/**
 * Sanitize
 *
 * This will handle the sanitize.
 *
 * @package Proto\Utils\Filter
 */
class Sanitize extends Filter
{
	/**
     * This will sanitize an email.
     *
     * @param string|null $email
     * @return string|null
     */
    public static function email(?string $email): ?string
    {
        return static::filter($email, FILTER_SANITIZE_EMAIL);
    }

    /**
     * This will sanitize a phone.
     *
     * @param string $phone
     * @return string|null
     */
    public static function phone($phone = null): ?string
    {
        if (empty($phone))
        {
            return null;
        }

        $phone = \preg_replace('/[^0-9]/', '', $phone);
        return (strlen($phone) === 10)? $phone : null;
    }

    /**
     * This will sanitize an ip.
     *
     * @param string $ip
     * @return string|null
     */
    public static function ip($ip = null): ?string
    {
        if (empty($ip))
        {
            return null;
        }

        return \preg_replace('/[^0-9.]/', '', $ip);
    }

    /**
     * This will sanitize a mac.
     *
     * @param string $mac
     * @return string|null
     */
    public static function mac($mac = null): ?string
    {
        if (empty($mac))
        {
            return null;
        }

        return \preg_replace('/[^0-9a-zA-Z.-]/', '', $mac);
    }

    /**
     * This will sanitize a bool.
     *
     * @param string $bool
     * @return bool|null
     */
    public static function bool($bool = null): ?bool
    {
        if (is_null($bool))
        {
            return null;
        }

        return (bool)$bool;
    }

    /**
     * This will sanitize an int.
     *
     * @param int $int
     * @return int|null
     */
    public static function int($int): ?int
    {
        $result = static::filter($int, FILTER_SANITIZE_NUMBER_INT);
        return ($result !== null)? (int)$result : null;
    }

    /**
     * This will sanitize a float.
     *
     * @param float $number
     * @return float|bool
     */
    public static function float($number): ?float
    {
        $result = \filter_var($number, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        return ($result !== false)? (float)$result : null;
    }

    /**
     * This will sanitize a string.
     *
     * @param string $string
     * @return string|null
     */
    public static function string($string): ?string
    {
        return static::filter($string, FILTER_UNSAFE_RAW);
    }

    /**
     * This will sanitize a url.
     *
     * @param string $url
     * @return string|null
     */
    public static function url($url): ?string
    {
        return static::filter($url, FILTER_SANITIZE_URL);
    }

    /**
     * This will sanitize a domain.
     *
     * @param string $url
     * @return string|null
     */
    public static function domain($url): ?string
    {
        return static::url($url);
    }

    /**
     * This will validate the key by flag.
     *
     * @param mixed $key
     * @param mixed $flag
     * @return mixed
     */
    protected static function filter($key, $flag)
    {
        if (is_null($key))
        {
            return null;
        }

        return \filter_var($key, $flag, FILTER_NULL_ON_FAILURE);
    }
}