<?php declare(strict_types=1);
namespace Tests\Unit\Controllers\Twilio;

use Proto\Integrations\Sms\TwilioMessageService;
use App\Models\Twilio\TwilioBrands;
use App\Controllers\Sms\Twilio\TwilioBrandController;
use Tests\MockTrait;
use Tests\Unit\Controllers\Twilio\Dto\BasicTestSettings;

/**
 * TwilioBrandControllerTest
 *
 * This will test all of the methods in the TwilioBrandController class.
 *
 * @package Tests\Unit\Controllers\Twilio
 */
class TwilioBrandControllerTest extends TwilioTestBase
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
    protected string $controllerName = TwilioBrandController::class;

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
    protected string $modelName = TwilioBrands::class;

    /**
     * This will test a successful outcome on the createBrand method.
     *
     * @return void
     */
    public function testCreateBrandSuccess(): void
    {
        $this->basicTest(new BasicTestSettings(
            apiMethod: 'createA2pBrand',
            controllerMethod: 'createBrand',
            apiMethodReturn: (object)[
                // Actual responses will contain more information, this is all that's necessary for the test //
                'sid' => 'BN0044409f7e067e279523808d267e2d85',
                'status' => 'PENDING'
            ],
            modelMethod: 'update',
            controllerMethodArguments: [
                'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX8',
                'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX1'
            ],
            responseType: 'success'
        ));
    }

    /**
     * This will test a failure outcome on the createA2pBrand method.
     *
     * @return void
     */
    public function testCreateBrandError(): void
    {
        $this->basicErrorTest(new BasicTestSettings(
            apiMethod: 'createA2pBrand',
            controllerMethod: 'createBrand',
            controllerMethodArguments: [
                'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX8',
                'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX1'
            ]
        ));
    }

    /**
     * This will test the checkBrandStatus method with an error outcome.
     *
     * @return void
     */
    public function testGetBrandStatusError(): void
    {
        $this->basicErrorTest(new BasicTestSettings(
            apiMethod: 'getBrandStatus',
            controllerMethodArguments: ['BNXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX']
        ));
    }

    /**
     * This will test the checkBrandStatus method with a successful result.
     *
     * @return void
     */
    public function testGetBrandStatusSuccess(): void
    {
        $this->basicTest(new BasicTestSettings(
            apiMethod: 'getBrandStatus',
            apiMethodReturn: (object)[
                // Actual responses will contain more information, this is all that's necessary for the test //
                'sid' => 'BNXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'status' => 'PENDING'
            ],
            modelMethod: 'update',
            controllerMethodArguments: [
                'BNXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
            ],
            controllerResponseEquals: (object)[
                'sid' => 'BNXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'status' => 'PENDING'
            ]
        ));
    }

    /**
     * This will test the handleBrandFailed method.
     *
     * @return void
     */
    public function testHandleBrandFailed(): void
    {
        $errorMock = $this->createSimpleMock(
            'errorModel',
            'add',
            true
        );

        $this->controller = $this->createController(
            errorMock: $errorMock
        );

        $brandResponse = (object)[
            "sid" => "BNXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
            "brand_feedback" => ["TAX_ID", "NONPROFIT"],
            "failure_reason" => "Registration error"
        ];
        $result = $this->controller->handleBrandFailed($brandResponse);

        $this->assertEquals(
            (object)[
                "success" => false,
                "message" => "The A2P brand failed to be created. Failure reason: Registration error. Brand Feedback: TAX_ID, NONPROFIT"
            ],
            $result
        );
    }

    /**
     * This will test the updateBrand method with a successful result.
     *
     * @return void
     */
    public function testUpdateBrandSuccess(): void
    {
        $this->basicTest(new BasicTestSettings(
            apiMethod: 'updateA2pBrand',
            apiMethodReturn: (object)[
                // Actual responses will contain more information, this is all that's necessary for the test //
                'sid' => 'BNXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'status' => 'PENDING'
            ],
            modelMethod: 'add',
            controllerMethod: 'updateBrand',
            controllerMethodArguments: [
                'BNXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
            ],
            responseType: 'success'
        ));
    }

    /**
     * This will test the updateBrand method with an error outcome.
     *
     * @return void
     */
    public function testUpdateBrandError(): void
    {
        $this->basicErrorTest(new BasicTestSettings(
            apiMethod: 'updateA2pBrand',
            controllerMethod: 'updateBrand',
            controllerMethodArguments: ['BNXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX']
        ));
    }

    /**
     * This will test the get pending brands method.
     *
     * @return void
     */
    public function testGetPendingBrands(): void
    {
        $this->useDatabase = true;

        $this->controller = new $this->controllerName(
            static::TEST_APP_CODE
        );

        $this->controller->saveToDatabase(
            new $this->modelName(),
            'setup',
            [
                'brand_status' => 'PENDING',
                'brand_sid' => 'BNXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
            ]
        );

        $result = $this->controller->getPendingBrands();

        /**
         * Testing the result does contain a row with the test app code.
         */
        $appCodes = [];
        foreach ($result as $client)
        {
            $appCodes[] = $client->app_code;
        }
        $this->assertContains(
            static::TEST_APP_CODE,
            $appCodes
        );

        /**
         * Testing the result only contains rows with a status of 'PENDING' or 'IN_PROGRESS'.
         */
        $containsOnlyPendingAndInProgress = true;
        foreach ($result as $client)
        {
            $status = $client->brand_status;
            if ($status !== 'PENDING' && $status !== 'IN_PROGRESS')
            {
                $containsOnlyPendingAndInProgress = false;
                break;
            }
        }
        $this->assertTrue($containsOnlyPendingAndInProgress, "The result contains a row with a status other than 'PENDING' or 'IN_PROGRESS'.");
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
            $this->controller->deleteFromDatabase('TwilioBrands', [
                'app_code' => static::TEST_APP_CODE
            ]);

            $this->controller->deleteTestErrors(static::TEST_APP_CODE, "Failure reason: Registration error Brand Feedback: TAX_ID, NONPROFIT");
        }
    }
}