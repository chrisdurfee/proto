<?php declare(strict_types=1);
namespace Tests\Unit\Controllers\Twilio;

use App\Controllers\Sms\Twilio\TwilioNumberController;
use App\Models\Twilio\TwilioNumbers;
use Tests\MockTrait;
use Tests\Unit\Controllers\Twilio\Dto\BasicTestSettings;
use App\Models\Zipwhip\ZipwhipDeletedNumbers;
use App\Services\Sms\Twilio\TwilioNumbersService;

/**
 * TwilioNumberControllerTest
 *
 * This will test all of the methods in the TwilioNumbersController class.
 *
 * @package Tests\Unit\Controllers\Twilio
 */
class TwilioNumberControllerTest extends TwilioTestBase
{
    /**
     * This will add additional methods to help work with mock objects.
     */
    use MockTrait;

    /**
     * This is the qulified name of the controller class that will be tested.
     *
     * @var string $controllerName
     */
    protected string $controllerName = TwilioNumberController::class;

    /**
     * This is the qulified name of the api class used by the controller.
     *
     * @var string $apiName
     */
    protected string $apiName = TwilioNumbersService::class;

    /**
     * This is the qulified name of the model class used by the controller.
     *
     * @var string $modelName
     */
    protected string $modelName = TwilioNumbers::class;

    /**
     * This will test the putNumber method.
     *
     * @return void
     */
    // public function testPutNumber(): void
    // {
    //     /**
    //      * This test doesn't require data to be deleted.
    //      */
    //     $this->deleteData = false;

    //     $controller = new $this->controllerName();
    //     $result = $controller->putNumber((object)[
    //         'appCode' => '113307008',
    //         'phoneNumber' => '+13857876638',
    //         'clientId' => 101,
    //         'phoneSid' => 'PN9ef9f276b4fa122ed26b7732d54ea76e',
    //         'phoneStatus' => 'completed'
    //     ]);

    //     $this->assertEquals(true, $result);
    // }

    /**
     * This will test the createHostedNumberOrder method with a successful outcome.
     *
     * @return void
     */
    public function testCreateHostedNumberOrderSuccess(): void
    {
        $this->basicTest(new BasicTestSettings(
            apiMethod: 'createHostedNumberOrder',
            apiMethodReturn: (object)[
                'sid' => 'HRXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'status' => 'received'
            ],
            modelMethod: 'setup',
            controllerMethod: 'createHostedNumberOrder',
            controllerMethodArguments: ['+987654321'],
            controllerResponseEquals: (object)[
                'sid' => 'HRXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'status' => 'received',
                'success' => true
            ]
        ));
    }

    /**
     * This will test the createHostedNumberOrder method with an error outcome.
     *
     * @return void
     */
    public function testCreateHostedNumberOrderError(): void
    {
        $this->basicErrorTest(new BasicTestSettings(
            apiMethod: 'createHostedNumberOrder',
            controllerMethod: 'createHostedNumberOrder',
            controllerMethodArguments: ['+987654321'],
        ));
    }

    /**
     * This will test the updateNumberStatus method with a successful outcome.
     *
     * @return void
     */
    public function testUpdateNumberStatusSuccess(): void
    {
        if ($this->useDatabase)
        {
            $this->saveTestNumber();
        }

        $modelMock = $this->createSimpleMock(
            'model',
            'updatePhoneStatus',
            true
        );

        $this->controller = $this->createController(
            modelMock: $modelMock
        );

        $response = $this->controller->updateNumberStatus(
            'received'
        );

        if ($this->useDatabase)
        {
            $this->deleteTestNumber();
        }

        $this->assertEquals((object)[
            'success' => true,
            'message' => 'Updated number status to received.'
        ], $response);
    }

    /**
     * This will save a test number to the database.
     *
     * @return void
     */
    private function saveTestNumber(): void
    {
        $controller = $this->createController();

        $controller->saveToDatabase(
            method: 'setup',
            model: new $this->modelName(),
            data: [
                "phoneSid" => 'PXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                "phoneNumber" => '+1234567890',
                "phoneStatus" => 'pending'
            ]
        );
    }

    /**
     * This will delete the test number from the database.
     *
     * @return void
     */
    private function deleteTestNumber(): void
    {
        $this->controller->deleteFromDatabase('TwilioNumbers', [
            'app_code' => static::TEST_APP_CODE
        ]);
    }

     /**
     * This will test the updateNumberStatus method with a database error outcome.
     *
     * @return void
     */
    public function testUpdateNumberStatusDatabaseError(): void
    {
        $modelMock = $this->createCustomMock(
            $this->modelName,
            ['updatePhoneStatus', 'add']
        );
        $modelMock->method('updatePhoneStatus')->willReturn(false);
        $modelMock->method('add')->willReturn(true);

        $this->controller = new $this->controllerName(
            model: $modelMock // The model will always be mocked because this tests a db error.
        );
        $this->controller->setClient($this->clientMock);

        $response = $this->controller->updateNumberStatus(
            'received'
        );
        $this->assertEquals((object)[
            'success' => false,
            'message' => 'There was an error saving the number status to the database.'
        ], $response);
    }

    /**
     * This will test the handleNumberReceived method with a successful outcome.
     *
     * @return void
     */
    public function testHandleNumberReceivedSuccess(): void
    {
        $modelMock = $this->createSimpleMock(
            'model',
            'updatePhoneStatus',
            true
        );
        $apiMock = $this->createSimpleMock(
            'api',
            'updateHostedNumberStatus',
            (object)[
                'sid' => 'HRXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'incoming_phone_number_sid' => 'PN11111111111111111111111111111111',
                'status' => 'pending-verification'
            ]
        );

        $this->controller = $this->createController(
            modelMock: $modelMock,
            apiMock: $apiMock
        );

        $response = $this->controller->handleNumberReceived(
            'HRXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
        );

        $this->assertEquals((object)[
            'success' => true,
            'message' => 'Updated number status to pending-verification.'
        ], $response);
    }

    /**
     * This will test the handleNumberReceived method with an error outcome.
     *
     * @return void
     */
    public function testHandleNumberReceivedError(): void
    {
        $this->basicErrorTest(new BasicTestSettings(
            apiMethod: 'updateHostedNumberStatus',
            controllerMethod: 'handleNumberReceived',
            controllerMethodArguments: ['HRXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX']
        ));
    }

    /**
     * This will test the checkStatus method.
     *
     * @return void
     */
    public function testCheckStatus(): void
    {
        /**
         * Using the mock builder to add the getBy method to the model to properly
         * mock it.
         */
        $modelMock = $this->getMockBuilder($this->modelName)
        ->addMethods(['getBy'])
        ->getMock();
        $modelMock->method('getBy')->willReturn((object)[
            'phone_sid' => 'PN11111111111111111111111111111111'
        ]);

        $apiMock = $this->createSimpleMock(
            'api',
            'getHostedNumberOrder',
            (object)[
                'status' => 'received',
                'incoming_phone_number_sid' => 'PN11111111111111111111111111111111',
                'friendly_name' => 'friendly_name'
            ]
        );

        $this->controller = new $this->controllerName(
            model: $modelMock,
            api: $apiMock
        );
        $this->controller->setClient($this->clientMock);

        $response = $this->controller->checkStatus('+14153608311');

        $this->assertEquals(
            (object)[
                'result' => 'received',
                'phoneSid' => 'PN11111111111111111111111111111111',
                'friendlyName' => 'friendly_name',
                'message' => null
            ],
            $response
        );
    }

    /**
     * This will test the checkEligibility method with a successful outcome.
     *
     * @return void
     */
    public function testCheckEligibilitySuccess(): void
    {
        $apiMock = $this->createSimpleMock(
            'api',
            'checkEligibility',
            (object)[
                'valid' => false
            ]
        );

        $this->controller = new $this->controllerName(
            api: $apiMock,
        );
        $this->controller->setClient($this->clientMock);

        $result = $this->controller->checkEligibility('+1234567890');

        $this->assertEquals(false, $result);
    }

    /**
     * This will test the checkEligibility method with a failure outcome.
     *
     * @return void
     */
    public function testCheckEligibilityFailure(): void
    {
        $apiMock = $this->createSimpleMock(
            'api',
            'checkEligibility',
            (object)[
                'valid' => false
            ]
        );

        $this->controller = new $this->controllerName(
            api: $this->useApi ? new TwilioNumbersService() : $apiMock,
        );
        $this->controller->setClient($this->clientMock);

        $result = $this->controller->checkEligibility('+1234567890');

        $this->assertEquals(false, $result);
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
            $this->controller->deleteFromDatabase('TwilioNumbers', [
                'app_code' => static::TEST_APP_CODE
            ]);
            $this->controller->deleteTestErrors(static::TEST_APP_CODE, "There was an error saving the number status to the database.");

            ZipwhipDeletedNumbers::remove((object)[
                'app_code' => static::TEST_APP_CODE
            ]);
        }
    }
}