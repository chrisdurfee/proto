<?php declare(strict_types=1);
namespace Tests\Unit\Controllers\Twilio;

use App\Controllers\Sms\Twilio\TwilioMessageServiceController;
use Proto\Integrations\Sms\TwilioMessageService;
use App\Models\Twilio\TwilioMessagingServices;
use Tests\MockTrait;
use Tests\Unit\Controllers\Twilio\Dto\BasicTestSettings;

/**
 * TwilioMessageServiceControllerTest
 *
 * This will test all of the methods in the TwilioMessageServiceController class.
 */
class TwilioMessageServiceControllerTest extends TwilioTestBase
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
    protected string $controllerName = TwilioMessageServiceController::class;

    /**
     * This is the qulified name of the api class used by the controller.
     *
     * @var string $apiName
     */
    protected string $apiName = TwilioMessageService::class;

    /**
     * This is the qulified name of the model class used by the controller.
     *
     * @var string $modelName
     */
    protected string $modelName = TwilioMessagingServices::class;

    /**
     * This will test the addNumberToMessagingService method with a successful outcome.
     *
     * @return void
     */
    public function testAddNumberToMessagingServiceSuccess(): void
    {
        /**
         * Using the mock builder to add the getBy method to the model to properly
         * mock it.
         */
        $modelMock = $this->getMockBuilder($this->modelName)
        ->addMethods(['getBy'])
        ->getMock();
        $modelMock->method('getBy')->willReturn((object)[
            'message_service_sid' => 'MGXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
        ]);

        $apiMock = $this->createSimpleMock(
            'api',
            'addNumberToService',
            (object)[
                'sid' => 'PXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'service_sid' => 'MGXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'phone_number' => '+987654321'
            ]
        );

        $this->controller = $this->createController(
            apiMock: $apiMock,
            modelMock: $modelMock
        );

        if ($this->useDatabase)
        {
            /**
             * Saving data for the test.
             */
            $this->controller->saveToDatabase(
                model: new $this->modelName(),
                method: 'setup',
                data: [
                    'messageServiceSid' => 'MGXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                    'smsUseCaseStatus' => 'pending',
                ]
            );
        }

        $response = $this->controller->addNumberToMessagingService(
            'HRXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
        );
        $this->assertEquals((object)[
            'sid' => 'PXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'service_sid' => 'MGXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'phone_number' => '+987654321',
            'success' => true
        ], $response);
    }

    /**
     * This will test the addNumberToMessagingService method with an api error outcome.
     *
     * @return void
     */
    public function testAddNumberToMessagingServiceError(): void
    {
        /**
         * Using the mock builder to add the getBy method to the model to properly
         * mock it.
         */
        $modelMock = $this->getMockBuilder($this->modelName)
        ->addMethods(['getBy'])
        ->getMock();
        $modelMock->method('getBy')->willReturn((object)[
            'message_service_sid' => 'MGXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
        ]);

        $apiMock = $this->createSimpleMock(
            'api',
            'addNumberToService',
            (object)[
                'message' => 'Test error.',
                'code' => 400
            ]
        );

        $this->controller = new $this->controllerName(
            api: $apiMock,
            model: $modelMock
        );
        $this->controller->setClient($this->clientMock);

        $response = $this->controller->addNumberToMessagingService(
            'HRXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
        );
        $this->assertEquals((object)[
            'message' => 'Test error. Code: 400.',
            'success' => false
        ], $response);
    }

    /**
     * This will test the createMessagingService method with a successful outcome.
     *
     * @return void
     */
    public function testCreateMessagingServiceSuccess(): void
    {
        $this->basicTest(new BasicTestSettings(
            apiMethod: 'createService',
            apiMethodReturn: (object)[
                // Actual responses will contain more information, this is all that's necessary for the test //
                'sid' => 'MGXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            ],
            modelMethod: 'setup',
            controllerMethod: 'createMessagingService',
            controllerResponseEquals: (object)[
               'sid' => 'MGXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'success' => true
            ]
        ));
    }

    /**
     * This will test the createMessagingService method with a failed outcome.
     *
     * @return void
     */
    public function testCreateMessagingServiceError(): void
    {
        $this->basicErrorTest(new BasicTestSettings(
            apiMethod: 'createService',
            controllerMethod: 'createMessagingService',
        ));
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
            $this->controller->deleteFromDatabase('TwilioMessagingServices', [
                'app_code' => static::TEST_APP_CODE
            ]);

            $this->controller->deleteTestErrors(static::TEST_APP_CODE, 'Test error.');
            $this->controller->deleteTestErrors(static::TEST_APP_CODE, 'Test error. Code: 400.');
            $this->controller->deleteTestErrors(static::TEST_APP_CODE, 'The messaging service SID was not found in the database when trying to add the phone number to the messaging service.');
        }
    }
}