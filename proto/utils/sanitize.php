<?php declare(strict_types=1);
namespace Proto\Utils;

/**
 * Sanitize
 *
 * This will handle the sanitization of data.
 *
 * @package Proto\Utils
 */
class Sanitize extends Util
{
    /**
     * This will remove script tags from HTML.
     *
     * @param string $str
     * @return string
     */
    public static function cleanHtml(string $str): string
	{
		return trim(self::removeScripts($str));
	}

    /**
     * This will clean a column.
     *
     * @param string $col
     * @return string
     */
    public static function cleanColumn(string $col): string
    {
        return preg_replace('/[^a-zA-Z0-9_.]/', '', $col);
    }

    /**
     * This will replace script tags from an HTML string.
     *
     * @param string $str
     * @return string
     */
	public static function removeScripts(string $str): string
	{
        $replace = preg_replace('/(<script.*?(?:\/|&#47;|&#x0002F;)script)/i', '', $str);
        $replace = preg_replace('/(<\?.*\?>)/', '', $replace);
		return ($replace !== null)? $replace : $str;
    }

    /**
     * This will clean data by removing HTML and fixing
     * slashes.
     *
     * @param mixed $data
     * @return mixed
     */
    public static function clean($data)
    {
        if (is_null($data))
        {
            return $data;
        }

        /* we want to check to filter each property of an object
		or element in an array */
		if (is_array($data) || is_object($data))
		{
			foreach ($data as &$key)
			{
				if (is_array($key) || is_object($key))
				{
					/* we need to do a recursive search to filter
					any child objects */
					$key = self::clean($key);
				}
				else
				{
					$key = self::sanitizeString($key);
				}
			}
		}
		else
		{
			/* filter the data */
			$data = self::sanitizeString($data);
		}
		return $data;
    }

    /**
     * This will sanitize a string by removing HTML and fixing slashes.
     *
     * @param mixed $str
     * @return mixed
     */
    public static function sanitizeString($str): mixed
    {
        if (!is_string($str))
        {
            return $str;
        }

        $str = (string)$str;
        $str = strip_tags($str);
		$str = str_replace('\\\\', '\\', $str);
		$str = str_replace('\\\'', '\'', $str);
		return str_replace('\\"', '', $str);
    }

    /**
     * This will clean data to be rendered in html.
     *
     * @param mixed $data
     * @return mixed
     */
    public static function cleanHtmlEntities($data)
    {
        if (is_null($data))
        {
            return $data;
        }

        /* we want to check to filter each property of an object
		or element in an array */
		if (is_array($data) || is_object($data))
		{
			foreach ($data as &$key)
			{
                if (is_int($key) || is_bool($key) || is_null($key))
                {
                    continue;
                }

				if (is_array($key) || is_object($key))
				{
					/* we need to do a recursive search to filter
					any child objects */
					$key = self::cleanHtmlEntities($key);
				}
				else
				{
					$key = self::htmlEntities($key);
				}
			}
		}
		else
		{
			/* filter the data */
			$data = self::htmlEntities($data);
		}
		return $data;
    }

    /**
     * This will clean a string to be rendered in html.
     *
     * @param string $str
     * @return string
     */
    public static function htmlEntities(string $str): string
    {
        return \htmlentities($str, ENT_QUOTES, 'UTF-8');
    }
}
