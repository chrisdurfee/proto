<?php declare(strict_types=1);
namespace Proto\Utils;

/**
 * DateTime Utility Class
 *
 * Provides utility functions for date and time manipulation.
 *
 * @package Proto\Utils
 */
class DateTime
{
	/**
	 * Formats a date according to the given type.
	 *
	 * @param string $date
	 * @param string $type
	 * @return string|null
	 */
	public static function formatDate(string $date, string $type = 'standard'): ?string
	{
		if ($date === '0000-00-00' || strlen($date) < 4)
		{
			return null;
		}

		$format = match ($type)
		{
			'mysql' => 'Y-m-d', // YYYY-MM-DD
			default => 'm/d/Y'  // MM/DD/YYYY
		};

		$timestamp = strtotime($date);
		return ($timestamp !== false) ? date($format, $timestamp) : null;
	}

	/**
	 * Formats time based on the given type (12-hour or 24-hour).
	 *
	 * @param string $date
	 * @param int $type
	 * @return string
	 */
	public static function formatTime(string $date, int $type = 12): string
	{
		if (empty($date))
		{
			return '';
		}

		$format = ($type === 24) ? 'H:i:s' : 'h:i a';
		$timestamp = strtotime($date);
		return ($timestamp !== false) ? date($format, $timestamp) : '';
	}

	/**
	 * Formats a month to ensure it's in two-digit format.
	 *
	 * @param int $month
	 * @return string
	 */
	public static function formatMonth(int $month): string
	{
		return str_pad((string) $month, 2, '0', STR_PAD_LEFT);
	}

	/**
	 * Converts a given datetime string to server time.
	 *
	 * @param string $dateTime
	 * @return string
	 */
	public static function getServerTime(string $dateTime): string
	{
		return date('Y-m-d H:i:s', strtotime($dateTime));
	}

	/**
	 * Compares two dates and returns true if the first date is earlier than the second.
	 *
	 * @param string $start
	 * @param string $end
	 * @return bool
	 */
	public static function compareDates(string $start, string $end): bool
	{
		return strtotime($start) < strtotime($end);
	}

	/**
	 * Converts a date to a specific timezone.
	 *
	 * @param string $date
	 * @param string $timezone
	 * @param string $defaultTimezone
	 * @return string
	 */
	public static function convertTimezone(string $date, string $timezone, string $defaultTimezone = 'America/Denver'): string
	{
		$dt = new \DateTime($date, new \DateTimeZone($defaultTimezone));
		$dt->setTimezone(new \DateTimeZone($timezone));
		return $dt->format('Y-m-d H:i:s');
	}

	/**
	 * Gets the timezone offset between two timezones in seconds.
	 *
	 * @param string $timezone
	 * @param string $defaultTimezone
	 * @return int
	 */
	public static function getTimezoneOffset(string $timezone, string $defaultTimezone = 'America/Denver'): int
	{
		$default = new \DateTimeZone($defaultTimezone);
		$user = new \DateTimeZone($timezone);

		return $default->getOffset(new \DateTime()) - $user->getOffset(new \DateTime());
	}

	/**
	 * Returns a timezone abbreviation.
	 *
	 * @param string $timezone
	 * @return string
	 */
	public static function getTimezoneAbbreviation(string $timezone = 'America/Denver'): string
	{
		return (new \DateTimeZone($timezone))->getName();
	}

	/**
	 * Checks if a date has expired compared to another date.
	 *
	 * @param string $dateTime
	 * @param string $compareTime
	 * @return bool
	 */
	public static function isExpired(string $dateTime, string $compareTime): bool
	{
		return strtotime($dateTime) < strtotime($compareTime);
	}

	/**
	 * Adds a specific number of days to a date.
	 *
	 * @param string $date
	 * @param int $days
	 * @return string
	 */
	public static function addDays(string $date, int $days): string
	{
		return date('Y-m-d', strtotime("+$days days", strtotime($date)));
	}

	/**
	 * Returns a formatted full date like "September 5, 2021".
	 *
	 * @param string $date
	 * @return string
	 */
	public static function fullDate(string $date): string
	{
		return date('F j, Y', strtotime($date));
	}

	/**
	 * Gets the month and year of a date.
	 *
	 * @param string $date
	 * @return object
	 */
	public static function getMonthYear(string $date): object
	{
		return (object)[
			'month' => date('m', strtotime($date)),
			'year' => date('Y', strtotime($date))
		];
	}

	/**
	 * Returns whether a given date is a weekend.
	 *
	 * @param string $date
	 * @return bool
	 */
	public static function isWeekend(string $date): bool
	{
		return (date('N', strtotime($date)) >= 6);
	}

	/**
	 * Gets the next workday, skipping weekends.
	 *
	 * @param string $date
	 * @return string
	 */
	public static function getNextWorkDay(string $date): string
	{
		while (self::isWeekend($date))
		{
			$date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
		}
		return $date;
	}

	/**
	 * Formats an event ending time.
	 *
	 * @param string $startsAt
	 * @param int $length
	 * @return string|bool
	 */
	public static function getEventEndingTime(string $startsAt, int $length = 0): string|bool
	{
		if ($length <= 0)
		{
			return false;
		}

		$date = new \DateTime($startsAt);
		return $date->modify("+{$length} minutes")->format('h:i a');
	}

	/**
	 * Checks if two dates have the same day of the month.
	 *
	 * @param string $date1
	 * @param string $date2
	 * @return bool
	 */
	public static function isSameDayOfMonth(string $date1, string $date2): bool
	{
		return (date('d', strtotime($date1)) === date('d', strtotime($date2)));
	}
}
