<?php declare(strict_types=1);
namespace Tests\Feature\Proto\Dispatch\Drivers\Sms;

use Tests\Test;
use Proto\Dispatch\Drivers\Sms\TwilioDriver;
use App\Controllers\Sms\Twilio\TwilioNumberController;
use App\Models\Twilio\TwilioNumbers;

/**
 * TwilioDriverTest
 *
 * This will test the TwilioDriver class.
 *
 * @package Tests\Feature\Proto\Dispatch\Drivers\Sms
 */
final class TwilioDriverTest extends Test
{
    /**
     * This is the twilio driver.
     *
     * @var string $driverName
     */
    protected string $driverName = TwilioDriver::class;

    /**
     * These are the message settings.
     *
     * @var object $messageSettings
     */
    protected object $messageSettings;

    /**
     * This is the app account used for testing.
     *
     * @var string
     */
    protected const TEST_APP_CODE = "425460979";

    /**
     * this will setup each test.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->messageSettings = (object)[
            "session" => "+13857876638",
            "to" => "+13853152538",
            "message" => "This is a test message."
        ];

        /**
         * Making sure the test number is in the database before testing.
         */
        $controller = new TwilioNumberController(self::TEST_APP_CODE);
        $controller->saveToDatabase(
            new TwilioNumbers(),
            'setup',
            [
                'phoneNumber' => '+13852500015',
                'phoneStatus' => 'completed',
                'phoneSid' => 'PNecdf7c3767b3e7f9e8ac9f2ae3422fbf'
            ]
        );
    }

    /**
     * This will test the send method with a successful outcome.
     *
     * (A message will not be sent if the number isn't A2P registered.)
     *
     * @return void
     */
    public function testSendSuccess(): void
    {
        $driver = new $this->driverName();
        $result = $driver->send($this->messageSettings);

        $this->assertTrue($result->success, "Message did not send. Check the session id and phone number");
    }

    /**
     * This will test the send method with a successful outcome.
     *
     * @return void
     */
    public function testSendWrongFormatToSuccess(): void
    {
        $settings = $this->messageSettings;
        $settings->to = "3853152538";

        $driver = new $this->driverName();
        $result = $driver->send($settings);

        $this->assertTrue($result->success, "Message did not send. Check the session id and phone number");
    }
}