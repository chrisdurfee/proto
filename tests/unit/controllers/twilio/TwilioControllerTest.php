<?php declare(strict_types=1);
namespace Tests\Unit\Controllers\Twilio;

use App\Controllers\Sms\TwilioController;
use Tests\MockTrait;

/**
 * TwilioControllerTest
 *
 * This will test all of the methods in the TwilioController class.
 *
 * @package Tests\Unit\Controllers\Twilio
 */
class TwilioControllerTest extends TwilioTestBase
{
    /**
     * This will add additional methods to help work with mock objects.
     */
    use MockTrait;

    /**
     * This will test the
     * check eligibility method with
     * a fake number.
     *
     * @return void
     */
    public function testCheckEligibilityFalse(): void
    {
        $phoneNumber = '+15555555555';
        $this->controller = new TwilioController();
        $result = $this->controller->checkEligibility($phoneNumber);

        $this->assertFalse($result);
    }

    /**
     * This will test the
     * get eligibility method with
     * a fake number.
     *
     * @return void
     */
    public function testGetEligibility(): void
    {
        $phoneNumber = '+15555555555';
        $this->controller = new TwilioController();
        $result = $this->controller->getEligibility($phoneNumber);

        $this->assertEquals((object)[
            'valid' => false,
            'result' => false,
            'message' => 'Phone number is not eligible. INVALID_BUT_POSSIBLE'
        ], $result);
    }

    /**
     * This will test the get session method
     * with the dentalqore app code.
     *
     * @return void
     */
    public function testGetSession(): void
    {
        $DQ_APP_CODE = '113307008';
        $DQ_PHONE = '+13857876638';

        $this->controller = new TwilioController();
        $result = $this->controller->getSession($DQ_APP_CODE);

        $this->assertEquals($DQ_PHONE, $result);
    }

    public function testFindAppCodeByNumber(): void
    {
        $DQ_PHONE_NUMBER = '+13857876638';
        $this->controller = new TwilioController();
        $result = $this->controller->findAppCodeByNumber($DQ_PHONE_NUMBER);

        $this->assertEquals('113307008', $result);
    }
}