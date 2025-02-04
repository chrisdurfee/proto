<?php declare(strict_types=1);
namespace Tests\Unit\Proto\Utils;

use Tests\Test;
use Core\Models\Timezone;

/**
 * TimezoneTest
 *
 * This will test the timezone utility class.
 *
 * @package Tests\Unit\Utils
 */
class TimezoneTest extends Test
{
    /**
     * This will test the get time from zip method.
     *
     * @return void
     */
	public function testGetTimeFromZipNewJersey(): void
    {
        $timezoneModel = new Timezone();
        $NEW_JERSEY_ZIP = '08332';
        $result = $timezoneModel->getTimeFromZip($NEW_JERSEY_ZIP);

        $hour = date('H', strtotime($result->time));
        $expectedHour = date('H', strtotime('+2 hours'));

        $this->assertEquals(
            $expectedHour,
            $hour
        );
    }

    /**
     * This will test the get time from zip method.
     *
     * @return void
     */
    public function testGetTimeFromZipVirginia(): void
    {
        $timezoneModel = new Timezone();
        $RICHMOND_VA_ZIP = '23233';
        $result = $timezoneModel->getTimeFromZip($RICHMOND_VA_ZIP);

        $hour = date('H', strtotime($result->time));
        $expectedHour = date('H', strtotime('+2 hours'));

        $this->assertEquals(
            $expectedHour,
            $hour
        );
    }
}