<?php declare(strict_types=1);
namespace Tests\Unit\Controllers\Twilio;

use Proto\Integrations\Sms\TwilioHostedNumbers;
use App\Models\Twilio\TwilioLoas;
use App\Models\Twilio\TwilioError;
use App\Controllers\Sms\Twilio\TwilioLoaController;
use Tests\MockTrait;
use App\Controllers\AuthorizedRepresentativeController;

/**
 * TwilioLoaControllerTest
 *
 * This will test all of the methods in the TwilioLoaController class.
 *
 * @package Tests\Unit\Controllers\Twilio
 */
class TwilioLoaControllerTest extends TwilioTestBase
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
    protected string $controllerName = TwilioLoaController::class;

    /**
     * This is the qulified name of the api class used by the controller.
     *
     * @var string $apiName
     */
    protected string $apiName = TwilioHostedNumbers::class;

    /**
     * This is the qulified name of the model class used by the controller.
     *
     * @var string $modelName
     */
    protected string $modelName = TwilioLoas::class;

    /**
     * This will test a successful outcome on the sendLoa method.
     *
     * @return void
     */
    public function testSendLoaSuccess(): void
    {
        $controllerMock = $this->getMockBuilder(AuthorizedRepresentativeController::class)
        ->addMethods(['getByAppCode'])
        ->getMock();
        $controllerMock->method('getByAppCode')->willReturn((object)[
            'phone' => '555-555-5555',
            'title' => 'Title',
            'firstName' => 'First',
            'lastName' => 'Last',
            'email' => 'email@email.com',
            'jobPosition' => 'Job Position'
        ]);

        $apiMock = $this->createSimpleMock(
            'api',
            'createAuthorizationDocument',
            (object)[
                'sid' => 'PXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'status' => 'signing'
                // Actual responses will contain more information, this is all that's necessary for the test //
            ]
        );

        $modelMock = $this->createSimpleMock(
            'model',
            'setup',
            true
        );

        $this->controller = new $this->controllerName(
            api: $apiMock,
            authorizedRepController: $controllerMock,
            model: $this->useDatabase === true ? new TwilioLoas() : $modelMock
        );
        $this->controller->setClient($this->clientMock);

        $result = $this->controller->sendLoa(
            'HRXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'ADXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
        );

        $this->assertEquals(
            (object)[
                'status' => 'signing',
                'sid' => 'PXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'success' => true
            ],
            $result
        );
    }

    /**
     * This will test a successful outcome on the sendLoa method.
     *
     * @return void
     */
    public function testSendLoaError(): void
    {
        $controllerMock = $this->getMockBuilder(AuthorizedRepresentativeController::class)
        ->addMethods(['getByAppCode'])
        ->getMock();
        $controllerMock->method('getByAppCode')->willReturn(null);

        $modelMock = $this->createSimpleMock(
            'error',
            'add',
            true
        );

        $this->controller = new $this->controllerName(
            authorizedRepController: $controllerMock,
        );
        $this->controller->setClient($this->clientMock);

        $errorModel = $this->useDatabase === true ? new TwilioError() : $modelMock;
        $this->controller->setErrorModel($errorModel);

        $result = $this->controller->sendLoa(
            'HRXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'ADXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
        );

        $this->assertEquals(
            (object)[
                'success' => false,
                'message' => "Could not find authorized representative for client {$this->clientMock->app}"
            ],
            $result
        );
    }

    /**
     * This method is called after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        /**
         * Deleting the test records from the database.
         * This is only necessary when not using mock models.
         */
        if ($this->useDatabase === true && $this->deleteData === true)
        {
            $this->controller->deleteFromDatabase('TwilioLoas', [
                'app_code' => static::TEST_APP_CODE
            ]);

            $this->controller->deleteTestErrors(static::TEST_APP_CODE, "Could not find authorized representative for client {$this->clientMock->app}");
        }
    }
}