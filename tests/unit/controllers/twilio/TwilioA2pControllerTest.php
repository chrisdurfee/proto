<?php declare(strict_types=1);
namespace Tests\Unit\Controllers\Twilio;

use Proto\Integrations\Sms\TwilioTrustHub;
use App\Models\Twilio\TwilioA2pProfiles;
use App\Controllers\Sms\Twilio\TwilioA2pController;
use Tests\MockTrait;
use Tests\Unit\Controllers\Twilio\Dto\BasicTestSettings;

/**
 * TwilioProfilesControllerTest
 *
 * This will test all of the methods in the TwilioProfileController class.
 *
 * @package Tests\Unit\Controllers\Twilio
 */
class TwilioA2pControllerTest extends TwilioTestBase
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
    protected string $controllerName = TwilioA2pController::class;

    /**
     * This is the qulified name of the api class used by the controller.
     *
     * @var string $apiName
     */
    protected string $apiName = TwilioTrustHub::class;

    /**
     * This is the qulified name of the model class used by the controller.
     *
     * @var string $modelName
     */
    protected string $modelName = TwilioA2pProfiles::class;

    /**
     * This will test a success on the createProfile method.
     *
     * @return void
     */
    public function testCreateProfileSuccess(): void
    {
        $this->basicTest(new BasicTestSettings(
            controllerMethod: 'createProfile',
            apiMethod: 'createA2pProfile',
            apiMethodReturn: (object)[
                // Actual responses will contain more information, this is all that's necessary for the test //
                'sid' => 'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'status' => 'draft',
            ],
            modelMethod: 'setup',
            responseType: 'success'
        ));
    }

    /**
     * This will test a failure on the createProfile method.
     *
     * @return void
     */
    public function testCreateProfileError(): void
    {
        $this->basicErrorTest(new BasicTestSettings(
            apiMethod: 'createA2pProfile',
            controllerMethod: 'createProfile',
        ));
    }

    /**
     * This will test a successful outcome on the attachToProfile method.
     *
     * @return void
     */
    public function testAttachToProfileSuccess(): void
    {
        $apiMock = $this->createSimpleMock(
            'api',
            'attachToA2pProfile',
            (object)[
            'sid' => 'BVXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'trust_product_sid' => 'BU70cb4e72a8486190e181b25c1180c348',
            "account_sid" => "ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
            "object_sid" => "BU48886180931dcaa8cbdc2033cf880b7a",
        ]);

        $this->controller = $this->createController(
            apiMock: $apiMock
        );

        $response = $this->controller->attachToProfile(
            'BU70cb4e72a8486190e181b25c1180c348',
            'BU48886180931dcaa8cbdc2033cf880b7a'
        );

        $this->assertEquals((object)[
            'sid' => 'BVXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'trust_product_sid' => 'BU70cb4e72a8486190e181b25c1180c348',
            "account_sid" => "ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
            "object_sid" => "BU48886180931dcaa8cbdc2033cf880b7a",
            'method' => 'attachToProfile',
            'success' => true
        ], $response);
    }

    /**
     * This will test a failure on the attachToProfile method.
     *
     * @return void
     */
    public function testAttachToProfileError(): void
    {
        $this->basicErrorTest(new BasicTestSettings(
            apiMethod: 'attachToA2pProfile',
            controllerMethod: 'attachToProfile',
            controllerMethodArguments: [
                'BU70cb4e72a8486190e181b25c1180c348',
                'BU48886180931dcaa8cbdc2033cf880b7a'
            ]
        ));
    }

    /**
     * This will test a successful outcome on the evaluateProfile method.
     *
     * @return void
     */
    public function testEvaluateProfileSuccess(): void
    {
        $this->basicTest(new BasicTestSettings(
            apiMethod: 'evaluateA2pProfile',
            controllerMethod: 'evaluateProfile',
            apiMethodReturn: (object)[
                // Actual responses will contain more information, this is all that's necessary for the test //
                "sid" => "ELXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
                "account_sid" => "ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
                "policy_sid" => "RN670d5d2e282a6130ae063b234b6019c8",
                "trust_product_sid" => "BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
                "status" => "compliant",
            ],
            modelMethod: 'update',
            controllerMethodArguments: [
                'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
            ],
            responseType: 'success'
        ));
    }

    /**
     * This will test a failure outcome on the evaluateProfile method.
     *
     * @return void
     */
    public function testEvaluateProfileError(): void
    {
        $this->basicErrorTest(new BasicTestSettings(
            apiMethod: 'evaluateA2pProfile',
            controllerMethod: 'evaluateProfile',
            controllerMethodArguments: [
                'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
            ],
        ));
    }

    /**
     * This will test the evaluateProfile method with a noncompliant profile.
     * This always tests with the database.
     *
     * @return void
     */
    public function testEvaluateProfileNonCompliant(): void
    {
        $apiMock = $this->createSimpleMock(
            'api',
            'evaluateA2pProfile',
            (object)[
            // Actual responses will contain more information, this is all that's necessary for the test //
            "sid" => "ELXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
            "account_sid" => "ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
            "policy_sid" => "RN670d5d2e282a6130ae063b234b6019c8",
            "trust_product_sid" => "BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
            "status" => "noncompliant",
            "results" => [
                (object)[
                    'failure_reason' => 'Test error.'
                ]
            ]
        ]);

        $modelMock = $this->createCustomMock($this->modelName, [
            'update',
            'add'
        ]);
        $modelMock->method('update')->willReturn(true);
        $modelMock->method('add')->willReturn(true);

        $this->controller = $this->createController(
            apiMock: $apiMock,
            modelMock: $modelMock
        );

        $response = $this->controller->evaluateProfile(
            'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
        );
        $this->assertEquals((object)[
            'message' => 'The A2P profile is noncompliant.',
            'success' => false
        ], $response);
    }

    /**
     * This will test a successful outcome on the submitProfileForReview method.
     *
     * @return void
     */
    public function testSumbitProfileForReviewSuccess(): void
    {
        $this->basicTest(new BasicTestSettings(
            apiMethod: 'submitA2pProfileForReview',
            controllerMethod: 'submitProfileForReview',
            apiMethodReturn: (object)[
                // Actual responses will contain more information, this is all that's necessary for the test //
                "sid" => "BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
                "status" => "pending-review",
            ],
            modelMethod: 'update',
            controllerMethodArguments: [
                'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
            ],
            responseType: 'success'
        ));
    }

    /**
     * This will test a failure outcome on the submitProfileForReview method.
     *
     * @return void
     */
    public function testSumbitProfileForReviewError(): void
    {
        $this->basicErrorTest(new BasicTestSettings(
            apiMethod: 'submitA2pProfileForReview',
            controllerMethod: 'submitProfileForReview',
            controllerMethodArguments: [
                'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
            ],
        ));
    }

    /**
     * This will test the updateProfileStatus method with a successful outcome.
     *
     * @return void
     */
    public function testUpdateProfileStatusSuccess(): void
    {
        /**
         * This test is set up explicitly because it doesn't use an api endpoint.
         */

        $modelMock = $this->createSimpleMock(
            'model',
            'update',
            true
        );

        $this->controller = $this->createController(
            modelMock: $modelMock
        );

        $response = $this->controller->updateProfileStatus('in-review');
        $this->assertEquals((object)[
            'success' => true,
            'message' => 'Updated A2P profile status to in-review.'
        ], $response);
    }

    /**
     * This will test the updateProfileStatus method with an error outcome.
     *
     * @return void
     */
    public function testUpdateProfileStatusDatabaseError(): void
    {
        /**
         * This test is set up explicitly because it doesn't use an api endpoint.
         */

        $modelMock = $this->createSimpleMock(
            'model',
            'update',
            false
        ); // The model in this test always needs to be mocked because it's testing a db error.

        $this->controller = new $this->controllerName(
            model: $modelMock
        );
        $this->controller->setClient($this->clientMock);

        $response = $this->controller->updateProfileStatus('in-review');
        $this->assertEquals((object)[
            'success' => false,
            'message' => 'There was an error saving the A2P profile status to the database.'
        ], $response);
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
            $this->controller->deleteFromDatabase('TwilioA2pProfiles', [
                'app_code' => static::TEST_APP_CODE
            ]);

            $this->controller->deleteTestErrors(static::TEST_APP_CODE, '[{"failure_reason":"Test error."}]');
        }
    }
}