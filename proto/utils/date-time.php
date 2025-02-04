<?php declare(strict_types=1);
namespace Proto\Utils;

/**
 * DateTime
 *
 * This will handle the date time utils.
 *
 * @package Proto\Utils
 */
class DateTime extends Util
{
	/**
	 * This will format a date.
	 *
	 * @param string $date
	 * @param string $type
	 * @return string|null
	 */
    public static function formatDate(string $date, string $type = 'standard'): ?string
    {
        if (!isset($date) || $date === '0000-00-00' || strlen($date) < 4)
		{
			return null;
        }

		$format = 'm/d/Y';
		switch ($type)
		{
			case 'mysql':
				//YYYY-MM-DD
				$format = 'Y-m-d';
				break;
		}

		$strToTime = \strtotime("$date");
		if ($strToTime === false)
		{
			return null;
		}
		return date($format, $strToTime);
    }

	/**
	 * This will format time.
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

		if (strlen($date) <= 3)
		{
			$date = '0' . $date;
		}

		$type = (int)$type;
		$format = 'h:i a';

		switch ($type)
		{
			case 24:
				//HH:mm:ss
				$format = 'H:i:s';
				break;
		}

		$strToTime = \strtotime("$date");
		if ($strToTime === false)
		{
			return '';
		}
		return \date($format, $strToTime);
	}

	/**
     * This will format a month to be saved to the database.
     *
     * @param int $month
     * @return string
     */
    public static function formatMonth(int $month): string
    {
        if ($month < 10)
        {
            return '0' . $month;
        }

        return (string)$month;
    }

	/**
	 * This will format a date and time to the server time.
	 *
	 * @param string $dateTime
	 * @return string
	 */
	public static function getServerTime(string $dateTime): string
	{
		if (empty($dateTime))
		{
			return '';
		}

		return date('Y-m-d H:i:s', strtotime($dateTime));
	}

	/**
	 * This will take in a startDate, endDate, and a duration and give you the dateRange.
	 *
	 * @param string $start
	 * @param string $end
	 * @param string $duration
	 * @return object
	 */
	public static function dateRange(string $start, string $end, string $duration): object
	{
		$dateRange = (object) [];
		switch($duration)
        {
            case 'year':
				$start = self::createDate('Y', $start);
                $end = self::createDate('Y', $end);
                if(self::compareDates($start, $end) === True)
                {
                    $dateRange->startDate = date('Y-01-01', strtotime($start));
                    $dateRange->endDate = date('Y-12-31', strtotime($start));
                    break;
                }
                $dateRange->startDate = date('Y-01-01', strtotime($end));
                $dateRange->endDate = date('Y-12-31', strtotime($end));
                break;
            case 'quarter':
                $dateRange->endDate = self::createDate('Y-m-d',$end, "last sunday");
                $dateRange->startDate = self::createDate('Y-m-d', $dateRange->endDate,' -3 Months');
				break;
            case 'month':
                $dateRange->endDate = self::createDate('Y-m-d',$end, "last sunday");
                $dateRange->startDate = self::createDate('Y-m-d',$dateRange->endDate, ' -1 Months');
                break;
            case 'week':
                $dateRange->endDate = self::createDate('Y-m-d', $end, "last sunday");
                $dateRange->startDate = self::createDate('Y-m-d', $dateRange->endDate, ' -1 week ');
			case 'day':
				$dateRange->startDate = self::createDate('Y-m-d', $end, "yesterday");
				$dateRange->endDate = self::createDate('Y-m-d', $end);
            default:
                $dateRange->startDate = $start;
                $dateRange->endDate = $end;
				break;
        }

		return $dateRange;
	}

	/**
	 * This will create a date based on the format and pharse passed into it
	 *
	 * @param string $format
	 * @param string $date
	 * @param string|null $phrase
	 * @return string
	 */
	public static function createDate(string $format, string $date, ?string $phrase = null):string
	{
		if(is_null($phrase) === false)
        {
			return date($format, strtotime($date . $phrase));
        }

		return date($format, strtotime($date));
	}

	/**
	 * This will take in two dates and tell you if one is later than the other.
	 *
	 * @param string $start
	 * @param string $end
	 * @return bool
	 */
	public static function compareDates(string $start, string $end): bool
	{
		if ($start < $end)
		{
			return True;
		}

		return false;
	}

	/**
	 * This will get a range for a period.
	 *
	 * @param string $period
	 * @return object
	 */
	public static function getDuration(string $period): object
	{
		$response = (object)[
			'startDate' => '',
			'endDate' => ''
		];

		$date = date('Y-m-d');
        $dateTimeStamp = strtotime($date);
        $month = intval(date('n'));
        $year = intval(date('Y'));

		switch ($period)
		{
			case 'today':
				$response->startDate = $date;
				$response->endDate = $date;
				break;
			case 'yesterday':
				$date = date('Y-m-d', mktime(0, 0, 0, $month, date("d")-1, $year));
				$response->startDate = $date;
				$response->endDate = $date;
				break;
			case 'tomorrow':
				$date = date('Y-m-d', mktime(0, 0, 0, $month, date("d")+1, $year));
				$response->startDate = $date;
				$response->endDate = $date;
				break;
			case 'week':
				$day = date('l', $dateTimeStamp);

				$start = ($day === 'Sunday')? $dateTimeStamp : strtotime('last sunday', $dateTimeStamp);
				$response->startDate = date('Y-m-d', $start);

				$end = ($day === 'Saturday')? $dateTimeStamp : strtotime('next saturday', $dateTimeStamp);
				$response->endDate = date('Y-m-d', $end);
				break;
			case 'next_week':
                $dateTimeStamp = strtotime($date . " +1 week");

				$start = (date('l', $dateTimeStamp) === 'Sunday')? $dateTimeStamp : strtotime('last sunday', $dateTimeStamp);
				$response->startDate = date('Y-m-d', $start);
				$response->endDate = date('Y-m-d', strtotime('next saturday', strtotime($response->startDate)));
				break;
			case 'last_week':
                $dateTimeStamp = strtotime($date . " -1 week");

				$start = (date('l', $dateTimeStamp) === 'Sunday')? $dateTimeStamp : strtotime('last sunday', $dateTimeStamp);
				$response->startDate = date('Y-m-d', $start);
				$response->endDate = date('Y-m-d', strtotime('next saturday', strtotime($response->startDate)));
				break;
			case 'month':
                $start = mktime(0, 0, 0, $month, 1, $year);
				$response = static::getMonthRange($start);
				break;
			case 'last_month':
                $dateTimeStamp = strtotime($date . ' -1 month');
                $response = static::getMonthRange($dateTimeStamp);
				break;
			case 'next_month':
                $dateTimeStamp = strtotime($date . ' +1 month');
                $response = static::getMonthRange($dateTimeStamp);
				break;
			case 'year':
				$response->startDate = date('Y-m-d', mktime(0, 0, 0, 1, 01, $year));
				$response->endDate = date('Y-m-d', mktime(0, 0, 0, 12, 31, $year));
				break;
			case 'last_year':
				$year = date('Y', strtotime($date . ' -1 year'));
                $year = intval($year);

				$response->startDate = date('Y-m-d', mktime(0, 0, 0, 1, 1, $year));
				$response->endDate = date('Y-m-d', mktime(0, 0, 0, 12, 31, $year));
				break;
			case 'next_year':
				$year = date('Y', strtotime($date . ' +1 year'));
                $year = intval($year);

				$response->startDate = date('Y-m-d', mktime(0, 0, 0, 1, 1, $year));
				$response->endDate = date('Y-m-d', mktime(0, 0, 0, 12, 31, $year));
				break;
			case 'all':
				$companyStartDate = '2006-11-01';

				$response->startDate = date('Y-m-d', strtotime($companyStartDate));
				$response->endDate = date('Y-m-d', $dateTimeStamp);
				break;
		}

		return $response;
	}

    /**
     * this will get a month range.
     *
     * @param int $dateTimeStamp
     * @return object
     */
    public static function getMonthRange(int $dateTimeStamp): object
    {
        $month = intval(date('n', $dateTimeStamp));
        $year = intval(date('Y', $dateTimeStamp));
        $start = mktime(0, 0, 0, $month, 1, $year);

        $startDate = date('Y-m-d', $start);
        $lastDayOfMonth = intval(date('t', $start));
        $endDate = date('Y-m-d', mktime(0, 0, 0, $month, $lastDayOfMonth, $year));

        return (object)[
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
    }

	/**
	 * This will get the work week duration for a period.
	 *
	 * @param string $period
	 * @return object
	 */
	public static function getWorkWeekDuration(string $period): object
	{
		$response = (object)[
			'startDate' => '',
			'endDate' => ''
		];

		$date = date('Y-m-d');
        $month = intval(date('n'));
        $year = intval(date('Y'));
		$yesterday = date('w', mktime(0, 0, 0, $month,date('d') - 1, $year));

		/* we want to check if the period is for yesterday
		and if the day was sat or sun */
		if($period === 'yesterday' && ($yesterday == '0' || $yesterday == '6'))
		{
			$day = ($yesterday == '0')? date("d")-3 : date("d")-2;
			$date = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
			$response->startDate = $date;
			$response->endDate = $date;
		}
		else
		{
			/* we want to get the duration from the
			standard duration function */
			$response = self::getDuration($period);
		}

		return $response;
	}

	/**
	 * This will get the holidays for a year.
	 *
	 * @param mixed $startingYear
	 * @return array
	 */
    public static function getHolidays(mixed $startingYear = null): array
    {
        $holidays = [];
        $startingYear = ($startingYear) ? $startingYear - 1 : date('Y') - 1;
		$length = date('Y') + 1;

        for ($year = $startingYear; $year <= $length; $year++)
        {
            // New Years Day
            $holidayDate = self::holidayIsWeekend("{$year}-01-01");
            $holidays[$holidayDate] = $holidayDate;

            // Memorial Day
            $holidayDate = self::holidayIsWeekend(date('Y-m-d', strtotime("last Monday of May $year")));
            $holidays[$holidayDate] = $holidayDate;

            // Independence Day
            $holidayDate = self::holidayIsWeekend("$year-07-04");
            $holidays[$holidayDate] = $holidayDate;

            // Labor Day
            $holidayDate = self::holidayIsWeekend(date("Y-m-d", strtotime("first Monday of September $year")));
            $holidays[$holidayDate] = $holidayDate;

            // Thanksgiving Day
            $holidayDate = self::holidayIsWeekend(date("Y-m-d", strtotime("fourth Thursday of November $year")));
            $holidays[$holidayDate] = $holidayDate;

            // Day After Thanksgiving Day
            $holidayDate = self::holidayIsWeekend(date("Y-m-d", strtotime('+1 day', strtotime("fourth Thursday of November $year"))));
            $holidays[$holidayDate] = $holidayDate;

			// Christmas eve
            $holidayDate = self::holidayIsWeekend("$year-12-24");
            $holidays[$holidayDate] = $holidayDate;

			// Christmas
            $holidayDate = self::holidayIsWeekend("$year-12-25");
            $holidays[$holidayDate] = $holidayDate;

			// Days between Christmas and New Year's
			if ($year >= 2023)
			{
				for ($i = 1; $i <= 6; $i++)
				{
					$timestamp = strtotime("{$year}-12-25 +{$i} days");
					$holidayDate = date("Y-m-d", $timestamp);
					$weekDay = date('D', $timestamp);
					if ($weekDay !== 'Sat' && $weekDay !== 'Sun')
					{
						$holidays[$holidayDate] = $holidayDate;
					}
				}
			}
        }
        return $holidays;
    }

    public static function holidayIsWeekend($date)
    {
        $year = date('Y', strtotime($date));
        $dow = date('D', strtotime($date));

        switch($date)
        {
            case "$year-01-01": // New Years
                if($dow == 'Sun')
                {
                    $date = "$year-01-02";
                }
                break;
            case "$year-07-04": // Independence Day
                if($dow == 'Sun')
                {
                    $date = "$year-07-05";
                }

                if($dow == 'Sat')
                {
                    $date = "$year-07-03";
                }
                break;
			case "$year-12-24": // Christmas eve
				if($dow == 'Sun')
                {
                    $date = "$year-12-22";
                }

                if($dow == 'Sat')
                {
                    $date = "$year-12-23";
                }
                break;
            case "$year-12-25": // Christmas Day
                if($dow == 'Sun')
                {
                    $date = "$year-12-26";
                }

                if($dow == 'Sat')
                {
                    $date = "$year-12-24";
                }
                break;
			case "$year-01-01": // New Year's
                if($dow == 'Sun')
                {
                    $date = "$year-01-02";
                }

                if($dow == 'Sat')
                {
					$year = $year - 1;
                    $date = "$year-12-31";
                }
                break;
        }
        return $date;
    }

	public static function isWeekend($date): bool
	{
		return (date('N', strtotime($date)) >= 6);
	}

    /**
     * This will return a date in the format "September 5, 2021"
     *
     * @param string $date
     * @param int|null $startTime
     * @return string
     */
    public static function fullDate(string $date, ?int $startTime = null): string
    {
        $startTime = $startTime ?? strtotime('now');
        return date('F j, Y', strtotime($date, $startTime));
    }

	/**
	 * This will convert a date to the correct timezone
	 *
	 * @param string $date
	 * @param string $timezone
	 * @param string $defaultTimezone
	 * @return string
	 */
	public static function convertTimezone(string $date, string $timezone, string $defaultTimezone = 'America/Denver'): string
    {
        if (strtolower($timezone) === $defaultTimezone)
        {
            return $date;
        }

		if (empty($date) === true)
		{
			$date = 'now';
		}

		if (empty($timezone) === true)
		{
			$timezone = $defaultTimezone;
		}

        $time = strtotime($date);
        date_default_timezone_set($timezone);
        $timestamp = date('Y-m-d H:i:s', $time);
        date_default_timezone_set($defaultTimezone);

        return $timestamp;
    }

	/**
	 * This will get the offset in seconds between two timezones
	 *
	 * @param string $timezone
	 * @param string $defaultTimezone
	 * @return int
	 */
	public static function getTimezoneOffset(string $timezone, string $defaultTimezone = 'America/Denver'): int
	{
		$default = new \DateTime('now', new \DateTimeZone($defaultTimezone));
		$user = new \DateTime('now', new \DateTimeZone($timezone));

		return $default->getOffset() - $user->getOffset();
	}

	/**
	 * This will return a timezone abbreviation
	 *
	 * @param string $timezone
	 * @return string
	 */
	public static function getTimezoneAbbreviation(string $timezone = 'America/Denver'): string
	{
		$dateTime = new \DateTime();
		$dateTime->setTimeZone(new \DateTimeZone($timezone));
		return $dateTime->format('T');
	}

	/**
	 * This will check is a date is expired.
	 *
	 * @param string $dateTime
	 * @param string $compareTime
	 * @return bool
	 */
	public static function isExpired(string $dateTime, string $compareTime): bool
	{
		return (strtotime($dateTime) < strtotime($compareTime));
	}

	/**
	 * This will get the ending time of an event.
	 *
	 * @param string $startsAt
	 * @param int $length
	 * @return string|bool
	 */
	public static function geEventEndingTime(string $startsAt, int $length = 0): mixed
	{
		if (!$length || $length <= 0)
		{
			return false;
		}

		$date = new \DateTime($startsAt);
		return $date->modify("+" . $length . ' minutes')->format('h:i a');
	}

    public static function getWorkDays($startDate, $endDate, ?string $type = null): object
    {
        $holidays = self::getHolidays();
        $time = (object) [];
        $time->workDays = 0;
        $time->totalDays = 0;
        $time->date = $startDate;
        $time->startDate = $startDate;

        $monthCompare = date('m', strtotime($time->date));
        while ($time->date < $endDate)
        {
            if (self::workDay($time->date, $holidays))
            {
                $time->workDays++;
            }

            $time->date = date('Y-m-d', strtotime($time->date . ' +1 day'));

            if ($type === 'month')
            {
                $time->month = date('m', strtotime($time->date));

                if ($time->month != $monthCompare || $time->date == $endDate)
                {
                    $time->days[] = $time->workDays;
                    $time->totalDays += $time->workDays;
                    $monthCompare = $time->month;
                    $time->workDays = 0;
                }
            }

            if ($type === "week")
            {
                $compare = date('w', strtotime($time->date));
                if ($compare == 1 || $time->date == $endDate)
                {
                    $time->days[] = $time->workDays;
                    $time->totalDays += $time->workDays;
                    $time->workDays = 0;
                }

            }
        }

        return $time;
    }


    public static function workDay($date, $holidays)
    {
        $weekDay = date('N', strtotime($date));
        if ($weekDay < 6 && !isset($holidays[$date]))
        {
            return true;
        }

        return false;
    }

	/**
	 * This will get the next work day.
	 *
	 * @param string $date
	 * @return string
	 */
	public static function getNextWorkDay(string $date): string
    {
        $isWeekDay = static::workDay($date, static::getHolidays());
		if ($isWeekDay)
		{
			return $date;
		}

		$date = date('Y-m-d', strtotime($date . ' +1 day'));
		return static::getNextWorkDay($date);
    }

	/**
	 * This will get the month and year of a date.
	 *
	 * @param string $date
	 * @return object
	 */
	public static function getMonthYear(string $date): object
	{
		$month = date('m', strtotime($date));
		$year = date('Y', strtotime($date));
		return (object)[
			'month' => $month,
			'year' => $year
		];
	}

	/**
	 * This will get the month from a date.
	 *
	 * @param string $date
	 * @return string
	 */
	public static function month(string $date): string
	{
		return date('m', strtotime($date));
	}

	/**
	 * This will get the day from a date.
	 *
	 * @param string $date
	 * @return string
	 */
	public static function day(string $date): string
	{
		return date('d', strtotime($date));
	}

	/**
	 * This will check if the date is the same day of the month
	 * for two dates.
	 *
	 * @param string $date1
	 * @param string $date2
	 * @return bool
	 */
	public static function isSameDayOfMonth(string $date1, string $date2): bool
	{
		if (static::month($date1) == static::month($date2) && static::day($date1) == static::day($date2))
		{
			return true;
		}

		return false;
	}

	/**
	 * This will add days to a date.
	 *
	 * @param string $date1
	 * @param mixed $days
	 * @return string
	 */
	public static function addDays(string $date1, mixed $days): string
	{
		$days = (string)$days;
		return date('Y-m-d', strtotime("{$date1} + {$days} day"));
	}
}