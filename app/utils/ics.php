<?php declare(strict_types=1);
namespace App\Utils;

use Proto\Utils\Files\File;

/**
 * Ics
 *
 * This will set up an ics calendar file.
 *
 * @package App\Utils
 */
class Ics
{
	/**
	 * This will setup the calendar.
	 *
	 * @param object $entry
	 * @return void
	 */
	public function __construct(
		protected object $entry
	)
	{
	}

    /**
     * This will create an ics file.
     *
     * @param object $entry
     * @param string $path
     * @return bool
     */
    public static function make(object $entry, string $path): bool
    {
        $ics = new static($entry);
        return File::put($path, $ics->render());
    }

	/**
	 * This will get the entry location.
	 *
	 * @return string
	 */
	protected function getLocation(): string
	{
		$entry = $this->entry;
        if (!isset($entry->address))
        {
            return '';
        }

		return $entry->address . " " . $entry->city . "\, " . $entry->state . " " . $entry->zip;
	}

	/**
	 * This will get the uid.
	 *
	 * @param string $dateTime
	 * @return string
	 */
	protected function getUid(string $dateTime): string
	{
		return $dateTime . "-" . rand() . "-" . env('baseUrl');
	}

	/**
	 * This will render the ical.
	 *
	 * @return string
	 */
	public function render(): string
	{
		$dateTime = date('Ymd') . 'T' . date('His');
		$uid = $this->getUid($dateTime);

		$entry = $this->entry;
		$entryDateTime = $this->convertDate($entry->date, $entry->time);

		return <<<EOT
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//DentalQore CRM//NONSGML Scheduled Reminder//EN
METHOD:REQUEST
BEGIN:VEVENT
UID:{$uid}
DTSTAMP:{$dateTime}
DTSTART:{$entryDateTime}
SUMMARY:{$entry->summary}
LOCATION:{$this->getLocation()}
DESCRIPTION:{$entry->description}
BEGIN:VALARM
TRIGGER:-PT30M
ACTION:DISPLAY
DESCRIPTION:Reminder
END:VALARM
END:VEVENT
END:VCALENDAR
EOT;
	}

	/**
	 * This will format the date.
	 *
	 * @param string $date
	 * @param string $time
	 * @return string
	 */
	protected function convertDate(string $date = '', string $time = ''): string
	{
		$dateTime = strtotime($date . ' ' . $time);
		return date('Ymd', $dateTime) . 'T' . date('His', $dateTime);
	}
}