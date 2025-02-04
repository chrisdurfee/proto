<?php declare(strict_types=1);
namespace App\Utils;

use Proto\Utils\DateTime as DateTimeUtil;

/**
 * DueDateTrait
 *
 * This trait will handle all the due date related functions.
 *
 * @package App\Utils
 */
trait DueDateTrait
{
    /**
     * @var array $holidays
     */
    public array $holidays = [];

    /**
     * this will get the holidays for the year.
     *
     * @param mixed $startingYear
     * @return array
     */
    public function getHolidays(mixed $startingYear = null): array
    {
        if (count($this->holidays) > 0)
        {
            return $this->holidays;
        }

        return DateTimeUtil::getHolidays($startingYear);
    }

    /**
     * this will get the due date based off of allotted work days.
     * (weekends, holidays, and sometimes day(s) prior to or
     * after holidays, will not be returned)
     *
     * @param string $startDate
     * @param int $allottedWorkDays
     * @return string
     */
    public function getDueDate(string $startDate, int $allottedWorkDays = 0): string
    {
        $date = date('Y-m-d', strtotime($startDate));
        if ($allottedWorkDays > 0)
        {
            $holidays = $this->getHolidays();
        }

        while ($allottedWorkDays > 0)
        {
            $date = date('Y-m-d', strtotime($date . ' +1 day'));
            $weekDay = date('N', strtotime($date));

            if ($weekDay < 6 && !isset($holidays[$date]))
            {
                $allottedWorkDays--;
            }
        }

        return $date;
    }
}