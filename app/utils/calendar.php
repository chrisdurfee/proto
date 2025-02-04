<?php declare(strict_types=1);
namespace App\Utils;

/**
 * Calendar
 *
 * This will handle the calendar.
 *
 * @package App\Utils
 */
class Calendar
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
	 * This will setup the ical headers and render the file.
	 *
	 * @return void
	 */
	public function setup(): void
	{
		self::addHeaders();
		$this->render();
	}

	/**
	 * This will create the ical headers.
	 *
	 * @return void
	 */
	protected static function addHeaders(): void
	{
		header("Content-Type: text/Calendar");
		header("Content-Disposition: inline; filename=calendar.ics");
	}

	/**
	 * This will render the ical.
	 *
	 * @return void
	 */
	protected function render(): void
	{
		$entry = $this->entry;
		$ics = new Ics((object)[
			'date' => $entry->date,
			'time' => $entry->time,
			'summary' => "",
			'description' => "",
			'address' => $entry->address,
			'city' => $entry->city,
			'state' => $entry->state,
			'zip' => $entry->zip
		]);

		echo $ics->render();
	}
}