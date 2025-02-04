<?php declare(strict_types=1);
namespace Tests\Unit\Controllers\Twilio;

use Proto\Integrations\Sms\TwilioMessageService;
use App\Models\Twilio\TwilioCampaigns;
use App\Controllers\Sms\Twilio\TwilioCampaignController;
use Tests\MockTrait;
use Tests\Unit\Controllers\Twilio\Dto\BasicTestSettings;

/**
 * TwilioCampaignControllerTest
 *
 * This will test all of the methods in the TwilioCampaignController class.
 *
 * @package Tests\Unit\Controllers\Twilio
 */
class TwilioCampaignControllerTest extends TwilioTestBase
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
    protected string $controllerName = TwilioCampaignController::class;

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
    protected string $modelName = TwilioCampaigns::class;

    /**
     * This will test a successful outcome on the createCampaign method.
     *
     * @return void
     */
    public function testCreateCampaignSuccess(): void
    {
        $this->basicTest(new BasicTestSettings(
            apiMethod: 'createCampaign',
            apiMethodReturn: (object)[
                // Actual responses will contain more information, this is all that's necessary for the test //
                'campaign_id' => 'CFOOBAR',
                'campaign_status' => 'PENDING'
            ],
            modelMethod: 'update',
            controllerMethodArguments: [
                'MGXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'BNXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
            ],
            responseType: 'success'
        ));
    }

    /**
     * This will test a failure outcome on the createCampaign method.
     *
     * @return void
     */
    public function testCreateCampaignError(): void
    {
        $this->basicErrorTest(new BasicTestSettings(
            apiMethod: 'createCampaign',
            controllerMethodArguments: [
                'MGXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'BNXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
            ]
        ));
    }

    /**
     * This will test the getCampaignStatus method with an error outcome.
     *
     * @return void
     */
    public function testGetCampaignStatusError(): void
    {
        $this->basicErrorTest(new BasicTestSettings(
            apiMethod: 'getCampaignStatus',
            controllerMethodArguments: ['MGXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'],
            controllerResponseEquals: (object)[
                'message' => 'There was an error getting the campaign status.',
                'success' => false
            ]
        ));
    }

    /**
     * This will test the getCampaignStatus method with a successful result.
     *
     * @return void
     */
    public function testGetCampaignStatusSuccess(): void
    {
        $this->basicTest(new BasicTestSettings(
            apiMethod: 'getCampaignStatus',
            apiMethodReturn: (object)[
                // Actual responses will contain more information, this is all that's necessary for the test //
                'campaign_id' => 'CFOOBAR',
                'campaign_status' => 'PENDING'
            ],
            modelMethod: 'update',
            controllerMethodArguments: [
                'MGXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
            ],
            controllerResponseEquals: (object)[
                'campaign_id' => 'CFOOBAR',
                'status' => 'PENDING',
                'campaign_status' => 'PENDING',
                'campaignId' => 'CFOOBAR'
            ]
        ));
    }

    /**
     * This will test the get pending campaigns method.
     *
     * @return void
     */
    public function testGetPendingCampaigns(): void
    {
        $this->useDatabase = true;

        $this->controller = new $this->controllerName(
            static::TEST_APP_CODE
        );

        $this->controller->saveToDatabase(
            new $this->modelName(),
            'setup',
            [
                'campaign_status' => 'PENDING',
                'campaign_id' => 'CFOOBAR'
            ]
        );

        $result = $this->controller->getPendingCampaigns();

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
            $status = $client->campaign_status;
            if ($status !== 'PENDING' && $status !== 'IN_PROGRESS')
            {
                $containsOnlyPendingAndInProgress = false;
                break;
            }
        }
        $this->assertTrue($containsOnlyPendingAndInProgress, "The result contains a row with a status other than 'PENDING' or 'IN_PROGRESS'.");
    }

    /**
     * This will test the update campaign status method.
     *
     * @return void
     */
    public function testUpdateCampaignStatus(): void
    {
        $this->useDatabase = true; // This test always needs to use the database //

        $this->controller = new $this->controllerName();
        $this->controller->setClient($this->clientMock);

        /**
         * Creating a test record in the database.
         */
        $this->controller->saveToDatabase(
            model: new $this->modelName,
            method: 'setup',
            data: [
                "campaignId" => "CFOOBAR",
                "campaignStatus" => "PENDING"
            ]
        );

        $result = $this->controller->updateCampaignStatus('VERIFIED');

        $this->assertEquals(true, $result);
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
            $this->controller->deleteFromDatabase('TwilioCampaigns', [
                'app_code' => static::TEST_APP_CODE
            ]);

            $this->controller->deleteTestErrors(static::TEST_APP_CODE, "Failure reason: Registration error Brand Feedback: TAX_ID, NONPROFIT");
        }
    }
}