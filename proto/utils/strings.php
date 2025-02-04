<?php declare(strict_types=1);
namespace Proto\Utils;

/**
 * Strings
 *
 * This will handle string manipulation.
 *
 * @package Proto\Utils
 */
class Strings extends Util
{
    /**
     * This will snake case a string.
     *
     * @param string $str
     * @return string
     */
    public static function snakeCase(string $str): string
    {
		$pattern = '/([a-z]|[0-9])([A-Z])/';
        return strtolower(preg_replace_callback($pattern, function ($matches) {
            return $matches[1] . '_' . $matches[2];
        },
        $str));
    }

    /**
     * This will kebab case a string.
     *
     * @param string $str
     * @return string
     */
    public static function kebabCase(string $str): string
    {
		$pattern = '/([a-z]|[0-9])([A-Z])/';
        return strtolower(preg_replace_callback($pattern, function ($matches) {
            return $matches[1] . '-' . $matches[2];
        },
        $str));
    }

    /**
     * This will add hyphens to a string.
     *
     * @param string $str
     * @return string
     */
    public static function hyphen(string $str): string
    {
        $str = str_replace("_", "-", $str);
        $str = str_replace(" ", "-", $str);

        $pattern = '/([a-z]|[0-9])([A-Z])/';
        return strtolower(preg_replace_callback($pattern, function($matches)
        {
            return $matches[1] . '-' . $matches[2];
        },
        $str));
    }

    /**
     * This will camel case a string.
     *
     * @param string $str
     * @return string
     */
    public static function camelCase(string $str): string
    {
        $pattern = '/(_|-)([a-z])/';
        return preg_replace_callback($pattern, function ($matches) {
            return strtoupper($matches[2]);
        },
        $str);
    }

    /**
     * This will convert a class name to a file name.
     *
     * @param string $className
     * @return string
     */
    public function classToFileName(string $className): string
    {
        $className = str_replace('\\', '/', $className);
        return static::hyphen($className);
    }

    /**
     * This will lowercase first char.
     *
     * @param string $str
     * @return string
     */
    public static function lowercaseFirstChar(string $str): string
    {
        return \lcfirst($str);
    }

    /**
     * This will map a snake case object to camel case.
     *
     * @param object $data
     * @return object
     */
    public static function mapToCamelCase(object $data): object
    {
        $obj = (object)[];

        foreach ($data as $key => $val)
		{
			if (\is_null($val))
			{
				continue;
			}

			$keyCamelCase = self::camelCase($key);
			$obj->{$keyCamelCase} = $val;
        }
        return $obj;
    }

    /**
     * This will pascal caps a string.
     *
     * @param string $str
     * @return string
     */
	public static function pascalCase(string $str): string
	{
		$pattern = '/(_|-)([a-z])/';

		$str = preg_replace_callback($pattern, function($matches)
		{
			return strtoupper($matches[2]);
		},
		$str);

		return ucfirst($str);
	}

    /**
     * This will stripe new lines.
     *
     * @param string $str
     * @return string
     */
    public static function stripNewlines(string $str): string
    {
        return trim(preg_replace('/\s+/', ' ', $str));
    }

    /**
     * This will remove dollar symbol from amount string.
     *
     * @param string $amount
     * @return string
     */
    public static function removeDollar(string $amount): string
    {
        return str_replace('$', '', $amount);
    }

    /**
     * This will filter the url string.
     *
     * @param string $url
     * @return string
     */
    public static function filterUrl(string $url): string
    {
        if (!$url)
        {
            return '';
        }

        $url = preg_replace('/(^\/|\/$)/', '', $url);
        $url = preg_replace('/(^http:\/\/|^https:\/\/)/', '', $url);
        $url = preg_replace('/(^www\.)/', '', $url);

        $parts = explode("/", $url, 2);
        return $parts[0];
    }

    /**
     * This will get the url path.
     *
     * @param string $url
     * @return string
     */
    public static function getUrlPath(string $url): string
    {
        if (!$url)
        {
            return '';
        }

        $parts = parse_url($url);
        return $parts['path'];
    }

    /**
     * This will clean a phone number.
     *
     * @param string $number
     * @return string
     */
    public static function cleanPhone(string $number): string
    {
        if (empty($number))
        {
            return '';
        }

        $number = preg_replace( '/[^0-9]/', '', $number);
        if (!isset($number))
        {
            return '';
        }

        if ($number[0] == '1')
		{
			$number = substr($number, 1);
		}
        return $number;
    }

    /**
     * This will clean a E.164 phone number.
     *
     * @param string $number
     * @return string
     */
    public static function cleanE164Phone(string $number): string
    {
        return preg_replace( '/[^0-9+]/', '', $number);
    }

    /**
     * This will format a phone number.
     *
     * With the default format being E.164.
     *
     * @param string $number
     * @param string $format
     * @return string
     */
    public static function formatPhone(string $number, string $format = 'E.164'): string
    {
        switch ($format)
        {
            case 'E.164': // E.164 example: +15555555555
                $number = self::cleanE164Phone($number);
                return self::checkPhoneE164($number) ? $number : self::formatE164Phone($number);

            case 'NANP': // North American Numbering Plan example: (555) 555-5555
                return preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $number);

            default:
                return $number;
        }
    }

    /**
     * This will format a phone number to E.164.
     *
     * @param string $number
     * @return string
     */
    public static function formatE164Phone(string $number): string
    {
        $number = self::cleanPhone($number);
        if (strlen($number) === 11)
        {
            return "+{$number}";
        }

        return "+1{$number}";
    }

    /**
     * This will check if a phone number is E.164.
     *
     * @param string $number
     * @return bool
     */
    public static function checkPhoneE164(string $number): bool
    {
        return (substr($number, 0, 2) === "+1");
    }

    /**
     * This will format an EIN number.
     *
     * @param string $ein
     * @return string
     */
    public static function formatEin(string $ein): string
    {
        $ein = preg_replace( '/[^0-9]/', '', $ein);

        return substr($ein, 0, 2) . '-' . substr($ein, 2, 7);
    }

    /**
     * This will remove all non alpha numeric chars.
     *
     * @param string $text
     * @param string $replace
     * @return string
     */
    public static function replaceNonAlphaNumeric(string $text, string $replace = ''): string
    {
        return preg_replace('/[^a-zA-Z0-9]/', $replace, $text);
    }

    public static function concatArray(array $strings)
    {
        $masterString = '';

        foreach ($strings as $string)
        {
            $masterString = self::concat($masterString, $string);
        }

        return $masterString;
    }

    /**
     * This will concat two strings.
     *
     * @param string $str1
     * @param string $str2
     * @return string
     */
    public static function concat(string $str1, string $str2): string
    {
        return $str1 . $str2;
    }

    /**
     * This will mask a stirng.
     *
     * @param string $string
     * @param int $length
     * @param string $mask
     * @return string
     */
    public static function mask(string $string, int $length = 4, string $mask = '*'): string
    {
        if (empty($string))
        {
            return '';
        }

        $stringLength = strlen($string);
        $chop = ($stringLength - $length);
        if ($chop < 0)
        {
            return $string;
        }

        $unmasked = substr($string, -4);
        $masked = array_fill(0, $chop, $mask);
        $masked = implode('', $masked);
        return $masked . $unmasked;
    }

    /**
	 * This will encode an object to param string.
	 *
	 * @param object $params
	 * @return string
	 */
	public static function encodeParams(object $params): string
	{
		$params = (array)$params;

		$encoded = [];
		foreach ($params as $key => $value)
		{
			$encoded[] = "{$key}={$value}";
		}
		return implode("&", $encoded);
	}

    /**
     * This will abbreviate a state name.
     *
     *  If the name is not found, it will return false.
     *
     * @param string $name
     * @return string|bool
     */
    public static function abbreviateState(string $name): string|bool
    {
        $states = [
           'alabama' => 'AL',
           'alaska' => 'AK',
           'arizona' => 'AZ',
           'arkansas' => 'AR',
           'california' => 'CA',
           'colorado' => 'CO',
           'connecticut' => 'CT',
           'district of columbia' => 'DC',
           'delaware' => 'DE',
           'florida' => 'FL',
           'georgia' => 'GA',
           'hawaii' => 'HI',
           'idaho' => 'ID',
           'illinois' => 'IL',
           'indiana' => 'IN',
           'iowa' => 'IA',
           'kansas' => 'KS',
           'kentucky' => 'KY',
           'louisiana' => 'LA',
           'maine' => 'ME',
           'maryland' => 'MD',
           'massachusetts' => 'MA',
           'michigan' => 'MI',
           'minnesota' => 'MN',
           'mississippi' => 'MS',
           'missouri' => 'MO',
           'montana' => 'MT',
           'nebraska' => 'NE',
           'nevada' => 'NV',
           'new hampshire' => 'NH',
           'new jersey' => 'NJ',
           'new mexico' => 'NM',
           'new york' => 'NY',
           'north carolina' => 'NC',
           'north dakota' => 'ND',
           'ohio' => 'OH',
           'oklahoma' => 'OK',
           'oregon' => 'OR',
           'pennsylvania' => 'PA',
           'rhode island' => 'RI',
           'south carolina' => 'SC',
           'south dakota' => 'SD',
           'tennessee' => 'TN',
           'texas' => 'TX',
           'utah' => 'UT',
           'vermont' => 'VT',
           'virginia' => 'VA',
           'washington' => 'WA',
           'west virginia' => 'WV',
           'wisconsin' => 'WI',
           'wyoming' => 'WY',
           'virgin islands' => 'V.I.',
           'guam' => 'GU',
           'puerto rico' => 'PR'
        ];

        $name = strtolower($name);
        foreach ($states as $state => $abbrev)
        {
            if ($name === strtolower($state) || $name === strtolower($abbrev))
            {
                return $abbrev;
            }
        }

        return false;
    }

    /**
     * This will abbreviate a canadian province name.
     *
     * If the name is not found, it will return false.
     *
     * @param string $name
     * @return string|bool
     */
    public static function abbreviateCanadianProvince(string $name): string|bool
    {
        $provinces = [
            'Alberta' => 'AB',
            'British Columbia' => 'BC',
            'Manitoba' => 'MB',
            'New Brunswick' => 'NB',
            'Newfoundland and Labrador' => 'NL',
            'Northwest Territories' => 'NT',
            'Nova Scotia' => 'NS',
            'Nunavut' => 'NU',
            'Ontario' => 'ON',
            'Prince Edward Island' => 'PE',
            'Quebec' => 'QC',
            'Saskatchewan' => 'SK',
            'Yukon' => 'YT'
        ];

        $name = strtolower($name);
        foreach ($provinces as $province => $abbrev)
        {
            if ($name === strtolower($province) || $name === strtolower($abbrev))
            {
                return $abbrev;
            }
        }

        return false;
    }
}
