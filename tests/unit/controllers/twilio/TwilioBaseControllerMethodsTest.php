<?php declare(strict_types=1);
namespace Tests\Unit\Controllers\Twilio;

use App\Controllers\Sms\Twilio\TwilioBrandController;
use Tests\MockTrait;

/**
 * TwilioBaseControllerMethodsTest
 *
 * This will test methods in the TwilioBaseController class.
 *
 * @package Tests\Unit\Controllers\Twilio
 */
class TwilioBaseControllerMethodsTest extends TwilioTestBase
{
    /**
     * This will add additional methods to help work with mock objects.
     */
    use MockTrait;

    /**
     * This is the qulified name of the controller class that will be tested.
     * This is used in the basicTest method of the TwilioBaseTest class.
     *
     * @var string $controllerName
     */
    protected string $controllerName = TwilioBrandController::class; // Using twilio brand because TwilioBaseController is abstract

    /**
     * This will test the formatClientObject method.
     *
     * This method is called in the constructor of the class
     * or when the client is manually set.
     *
     * @return void
     */
    public function testFormatClientObject(): void
    {
        $expected = (object)[
            'clientId' => $this->clientMock->id,
            'friendlyName' => static::TEST_APP_CODE,
            'app' => static::TEST_APP_CODE,
            'practice' => $this->clientMock->practice,
            'email' => $this->clientMock->email,
            'id' => static::TEST_APP_CODE,
            'provisionedPhone' => $this->clientMock->provisionedPhone,
            'mobile' => $this->clientMock->mobile,
            'phone' => $this->clientMock->phone,
            'contactNumber' => '+' . $this->clientMock->mobile,
            'contactName' => $this->clientMock->contactName,
            'facebook' => $this->clientMock->facebook,
            'twitter' => $this->clientMock->twitter,
            'youtube' => $this->clientMock->youtube,
            'instagram' => $this->clientMock->instagram,
            'pintrest' => $this->clientMock->pintrest,
            'google' => $this->clientMock->google,
            'yelp' => $this->clientMock->yelp,
            'site' => $this->clientMock->site,
            'websiteUrl' => $this->clientMock->site,
            'socialMediaUrls' => 'twitter.com, instagram.com, pinterest.com, yelp.com',
            'state' => $this->clientMock->state,
            'address' => '123 Test St'
        ];

        $this->controller = new $this->controllerName();
        $this->controller->setClient($this->clientMock);

        $client = $this->controller->getClient();

        $this->assertEquals(
            $expected,
            $client
        );
    }
}