<?php declare(strict_types=1);
namespace Tests\Unit\Controllers\Twilio;

use App\Models\Twilio\TwilioProfiles;
use App\Models\Twilio\TwilioError;
use App\Controllers\Sms\Twilio\TwilioProfileController;
use Tests\MockTrait;
use Tests\Unit\Controllers\Twilio\Dto\BasicTestSettings;
use App\Services\Sms\Twilio\TwilioProfilesService;
use App\Controllers\BusinessController;
use App\Controllers\AuthorizedRepresentativeController;

/**
 * TwilioProfilesControllerTest
 *
 * This will test all of the methods in the TwilioProfileController class.
 *
 * @package Tests\Unit\Controllers\Twilio
 */
class TwilioProfileControllerTest extends TwilioTestBase
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
    protected string $controllerName = TwilioProfileController::class;

    /**
     * This is the qulified name of the api class used by the controller.
     *
     * @var string $apiName
     */
    protected string $apiName = TwilioProfilesService::class;

    /**
     * This is the qulified name of the model class used by the controller.
     *
     * @var string $modelName
     */
    protected string $modelName = TwilioProfiles::class;

    /**
     * This will test a successful outcome on the createClientProfile method.
     *
     * @return void
     */
    public function testCreateClientProfileSuccess(): void
    {
        $this->basicTest(new BasicTestSettings(
            apiMethod: 'createClientProfile',
            apiMethodReturn: (object)[
                'sid' => 'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'status' => 'draft'
            ],
            responseType: 'success',
            modelMethod: 'setup',
        ));
    }

    /**
     * This will test a failed outcome on the createClientProfile method.
     *
     * @return void
     */
    public function testCreateClientProfileError(): void
    {
        $this->basicErrorTest(new BasicTestSettings(
            apiMethod: 'createClientProfile',
        ));
    }

    /**
     * This will test a successful outcome on the createEndUserBusinessInfo method.
     *
     * @return void
     */
    public function testCreateEndUserBusinessInfoSucess(): void
    {
        $controllerMock = $this->createCustomMock(
            BusinessController::class,
            ['getByApp']
        );

        /**
         * @var array $businessMockObj
         */
        $businessMockObj = (object)[
            'registrationNumber' => '123456789',
            'registrationIdentifier' => 'EIN',
            'name' => 'Test Business',
            'regionsOfOperation' => 'US_AND_CANADA',
            'companyType' => 'private',
            'businessType' => 'Corporation',
            'industry' => 'ONLINE'
        ];
        $controllerMock->method('getByApp')->willReturn($businessMockObj);

        $apiMock = $this->createCustomMock(
            $this->apiName,
            ['createEndUserBusinessInfo']
        );

        /** @var array $testAppCode */
        $testAppCode = self::TEST_APP_CODE;
        $apiMock->expects($this->once())
            ->method('createEndUserBusinessInfo')
            ->with($businessMockObj, $testAppCode) // Ignore warning. This is correct.
            ->willReturn((object)[
                "sid" => 'ITXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
            ]);

        $modelMock = $this->createSimpleMock(
            'model',
            'update',
            true
        );

        $this->controller = new $this->controllerName(
            api: $apiMock,
            model: $modelMock,
            businessController: $controllerMock
        );
        $this->controller->setClient($this->clientMock);

        $result = $this->controller->createEndUserBusinessInfo();

        $this->assertEquals(
            (object)[
                'sid' => 'ITXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'success' => true,
                'method' => 'createEndUserBusinessInfo'
            ],
            $result
        );
    }

    /**
     * This will the createEndUserBusinessInfo method with no business info in the database.
     *
     * @return void
     */
    public function testCreateEndUserBusinessInfoNoInfo(): void
    {
        $errorModelMock = $this->createCustomMock(TwilioError::class, ['add']);
        $errorModelMock->method('add')->willReturn(true);

        $controllerMock = $this->createCustomMock(BusinessController::class, ['getByApp']);
        $controllerMock->expects($this->once())->method('getByApp')->willReturn(null);

        $this->controller = new $this->controllerName(
            businessController: $controllerMock
        );
        $this->controller->setClient((object)[
            'app' => static::TEST_APP_CODE,
            'practice' => 'Test Practice',
            'email' => '',
            'id' => 2841,
            'provisionedPhone' => null,
            'mobile' => null,
            'phone' => null
        ]);
        $errorModel = $this->useDatabase ? new TwilioError() : $errorModelMock;
        $this->controller->setErrorModel($errorModel);

        $result = $this->controller->createEndUserBusinessInfo();

        $this->assertEquals(
            (object)[
                'success' => false,
                'message' => "No business information found for app code: " . static::TEST_APP_CODE . "."
            ],
            $result
        );
    }

    /**
     * This will test a successful outcome of the createEndUserAuthorizedRep method.
     *
     * @return void
     */
    public function testCreateEndUserAuthorizedRepSuccess(): void
    {
        $controllerMock = $this->createCustomMock(AuthorizedRepresentativeController::class, ['getByApp']);
        $controllerMock->method('getByApp')
            ->willReturn((object)[
                'firstName' => 'Test',
                'lastName' => 'Test',
                'email' => 'test@email.com',
                'phone' => '5555555555',
                'title' => 'title',
                'jobPosition' => 'CEO',
            ]);

        $friendlyName = 'Test Test ' . static::TEST_APP_CODE;
        $apiMock = $this->createCustomMock(
            $this->apiName,
            ['createEndUserAuthorizedRep']
        );

        /** @var array $classInstance */
        $classInstance = $this->isInstanceOf(\stdClass::class);
        /** @var array $isFriendlyName */
        $isFriendlyName = $this->equalTo($friendlyName);

        $apiMock->expects($this->once())
            ->method('createEndUserAuthorizedRep')
            ->with($classInstance, $isFriendlyName) // Ignore warning. This is correct.
            ->willReturn((object)[
                'sid' => 'ITXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'friendly_name' => $friendlyName
                // Actual responses will contain more information, this is all that's necessary for the test //
            ]);

        $this->controller = new $this->controllerName(
            api: $apiMock,
            authorizedRepController: $controllerMock
        );
        $this->controller->setClient($this->clientMock);

        $result = $this->controller->createEndUserAuthorizedRep();

        $this->assertEquals(
            (object)[
                'sid' => 'ITXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'friendly_name' => 'Test Test ' . static::TEST_APP_CODE,
                'method' => 'createEndUserAuthorizedRep',
                'success' => true
            ],
            $result
        );
    }

    /**
     * This will test a successful outcome of the createEndUserAuthorizedRep method.
     *
     * @return void
     */
    public function testCreateEndUserAuthorizedRepError(): void
    {
        $controllerMock = $this->createCustomMock(AuthorizedRepresentativeController::class, ['getByApp']);
        $controllerMock->expects($this->once())
            ->method('getByApp')
            ->willReturn(null);

        $errorMock = $this->createSimpleMock(
            'error',
            'add',
            true
        );

        $this->controller = new $this->controllerName(
            authorizedRepController: $controllerMock
        );
        $this->controller->setClient($this->clientMock);

        $errorModel = $this->useDatabase ? new TwilioError() : $errorMock;
        $this->controller->setErrorModel($errorModel);

        $result = $this->controller->createEndUserAuthorizedRep();

        $this->assertEquals(
            (object)[
                'success' => false,
                'message' => "No authorized representative found for app code: " . static::TEST_APP_CODE . "."
            ],
            $result
        );
    }

    /**
     * This will test a successful outcome on the createCustomerProfileAddress method.
     *
     * @return void
     */
    public function testCreateCustomerProfileAddressSuccess(): void
    {
        $controllerMock = $this->createCustomMock(AuthorizedRepresentativeController::class, ['getByApp']);
        $controllerMock->method('getByApp')
            ->willReturn((object)[
                'firstName' => 'Test',
                'lastName' => 'Test',
                'email' => 'test@email.com',
                'phone' => '5555555555',
                'title' => 'title',
                'jobPosition' => 'CEO',
            ]);

        $apiMock = $this->createCustomMock(
            $this->apiName,
            ['createCustomerProfileAddress']
        );

        /** @var array $classInstance */
        $classInstance = $this->isInstanceOf(\stdClass::class);
        /** @var array $phoneNumber */
        $phoneNumber = "+15555555555";

        $apiMock->expects($this->once())
            ->method('createCustomerProfileAddress')
            ->with($classInstance, $phoneNumber) // Ignore warning. This is correct.
            ->willReturn((object)[
                'sid' => 'ADXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
                // Actual responses will contain more information, this is all that's necessary for the test //
            ]);

        $modelMock = $this->createSimpleMock(
            'model',
            'update',
            true
        );

        $this->controller = new $this->controllerName(
            api: $apiMock,
            authorizedRepController: $controllerMock,
            model: $modelMock
        );
        $this->controller->setClient($this->clientMock);

        $result = $this->controller->createCustomerProfileAddress();

        $this->assertEquals(
            (object)[
                'sid' => 'ADXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'method' => 'createCustomerProfileAddress',
                'success' => true
            ],
            $result
        );
    }

    /**
     * This will test a success on the createCustomerDocument method.
     *
     * @return void
     */
    public function testCreateCustomerDocumentSuccess(): void
    {
        $this->basicTest(new BasicTestSettings(
            apiMethod: 'createCustomerDocument',
            apiMethodReturn: (object)[
                // Actual responses will contain more information, this is all that's necessary for the test //
                'sid' => 'RDXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'status' => 'draft'
            ],
            modelMethod: 'setup',
            controllerMethodArguments: [
                'ADXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
            ],
            responseType: 'success'
        ));
    }

    /**
     * This will test a failure on the createCustomerDocument method.
     *
     * @return void
     */
    public function testCreateCustomerDocumentError(): void
    {
        $this->basicErrorTest(new BasicTestSettings(
            apiMethod: 'createCustomerDocument',
            controllerMethodArguments: [
                'ADXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
            ]
        ));
    }

    /**
     * This will test a success on the attachToClientProfile method.
     *
     * @return void
     */
    public function testAttachToClientProfileSuccess(): void
    {
        $apiMock = $this->createCustomMock($this->apiName, ['attachToClientProfile']);
        $apiMock->method('attachToClientProfile')->willReturn((object)[
            "sid" => "BVXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
            "customer_profile_sid" => "BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
            "account_sid" => "ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
            "object_sid" => "ITXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
        ]);

        $this->controller = $this->createController(apiMock: $apiMock);

        $response = $this->controller->attachToClientProfile(
            'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'ITXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
        );

        $this->assertEquals((object)[
            "sid" => "BVXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
            "customer_profile_sid" => "BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
            "account_sid" => "ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
            "object_sid" => "ITXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
            'success' => true,
            'method' => 'attachToClientProfile'
        ], $response);
    }

    /**
     * This will test a failure on the attachToClientProfile method.
     *
     * @return void
     */
    public function testAttachToClientProfileError(): void
    {
        $this->basicErrorTest(new BasicTestSettings(
            apiMethod: 'attachToClientProfile',
            controllerMethodArguments: [
                'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'ITXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
            ]
        ));
    }


    /**
     * This will test a success on the evaluateClientProfile method.
     *
     * @return void
     */
    public function testEvaluateClientProfileSuccess(): void
    {
        $this->basicTest(new BasicTestSettings(
            apiMethod: 'evaluateClientProfile',
            apiMethodReturn: (object)[
                // Actual responses will contain more information, this is all that's necessary for the test //
                "sid" => "ELXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
                "account_sid" => "ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
                "policy_sid" => "RN806dd6cd175f314e1f96a9727ee271f4",
                "customer_profile_sid" => "BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
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
     * This will test a failure on the evaluateClientProfile method.
     *
     * @return void
     */
    public function testEvaluateClientProfileError(): void
    {
        $this->basicErrorTest(new BasicTestSettings(
            apiMethod: 'evaluateClientProfile',
            controllerMethodArguments: [
                'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
            ]
        ));
    }

    /**
     * This will test the evaluateClientProfile method when the profile is non-compliant.
     * This test is a special case because it needs to test with the database.
     *
     * @return void
     */
    public function testEvaluateClientProfileNonCompliant(): void
    {
        $apiMock = $this->createCustomMock(
            $this->apiName,
            ['evaluateClientProfile']
        );
        $apiMock->method('evaluateClientProfile')->willReturn((object)[
            // Actual responses will contain more information, this is all that's necessary for the test //
            "sid" => "ELXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
            "account_sid" => "ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
            "policy_sid" => "RN806dd6cd175f314e1f96a9727ee271f4",
            "customer_profile_sid" => "BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
            "status" => "noncompliant",
            "results" => [
                (object)[
                    'failure_reason' => 'Test error.'
                ]
            ]
        ]);

        $modelMock = $this->createCustomMock($this->modelName, ['update']);
        $modelMock->method('update')->willReturn(true);

        $errorMock = $this->createCustomMock(TwilioError::class, ['add']);
        $errorMock->method('add')->willReturn(true);

        $this->controller = $this->createController(
            modelMock: $modelMock,
            apiMock: $apiMock
        );

        $response = $this->controller->evaluateClientProfile(
            'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
        );

        $this->AssertEquals((object)[
            'message' => 'The client profile is noncompliant.',
            'success' => false
        ], $response);
    }

    /**
     * This will test a success on the submitClientProfileForReview method.
     *
     * @return void
     */
    public function testSubmitClientProfileForReviewSuccess(): void
    {
        $this->basicTest(new BasicTestSettings(
            apiMethod: 'submitClientProfileForReview',
            apiMethodReturn: (object)[
                // Actual responses will contain more information, this is all that's necessary for the test //
                'sid' => 'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                'status' => 'pending-review',
            ],
            modelMethod: 'setup',
            controllerMethodArguments: [
                'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
            ],
            responseType: 'success'
        ));
    }

    /**
     * This will test a failure on the submitClientProfileForReview method.
     *
     * @return void
     */
    public function testSubmitClientProfileForReviewError(): void
    {
        $this->basicErrorTest(new BasicTestSettings(
            apiMethod: 'submitClientProfileForReview',
            controllerMethodArguments: [
                'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
            ]
        ));
    }

    /**
     * This will test the updateA2pProfileStatus method with a successful outcome.
     *
     * @return void
     */
    public function testUpdateClientProfileStatusSuccess(): void
    {
        /**
         * This test is set up explicitly because it doesn't use an api endpoint.
         */

        $modelMock = $this->createCustomMock(
            $this->modelName,
            ['update']
        );
        $modelMock->method('update')->willReturn(true);

        $this->controller = $this->createController(
            modelMock: $modelMock
        );

        $response = $this->controller->updateStatus(
            'in-review',
            'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
        );
        $this->assertEquals((object)[
            'success' => true,
            'message' => 'Updated profile status to in-review.',
        ], $response);
    }

    /**
     * This will test the updateA2pProfileStatus method with a twilio-rejected status.
     *
     * @return void
     */
    public function testUpdateClientProfileStatusTwilioRejected(): void
    {
        /**
         * These methods will be used for the
         * saveToDatabase method as well as the logError method.
         */
        $modelMock = $this->createCustomMock(
            $this->modelName,
            ['update']
        );
        $modelMock->method('update')->willReturn(true);
        $errorMock = $this->createCustomMock(
            TwilioError::class,
            ['add']
        );
        $errorMock->method('add')->willReturn(true);

        $this->controller = $this->createController(
            modelMock: $modelMock,
            errorMock: $errorMock
        );

        $response = $this->controller->updateStatus(
            'twilio-rejected',
            'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
        );
        $this->assertEquals((object)[
            'success' => false,
            'message' => 'The client profile was rejected by Twilio.',
        ], $response);
    }

    /**
     * This will test the updateA2pProfileStatus method with a database error outcome.
     *
     * @return void
     */
    public function testUpdateClientProfileStatusDatabaseError(): void
    {
        /**
         * This test is set up explicitly because it doesn't use an api endpoint.
         */

        $modelMock = $this->createCustomMock(
            $this->modelName,
            ['update']
        );
        $modelMock->method('update')->willReturn(false);

        $this->controller = new $this->controllerName(
            model: $modelMock
        );
        $this->controller->setClient($this->clientMock);

        $response = $this->controller->updateStatus(
            'in-review',
            'BUXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
        );
        $this->assertEquals((object)[
            'success' => false,
            'message' => 'There was an error saving the profile status to the database.'
        ], $response);
    }

    /**
     * This will test the formatAddress method.
     *
     * @return void
     */
    public function testFormatAddress(): void
    {
        $this->useDatabase = false;

        $controller = new $this->controllerName();

        $client = (object)[
            'address' => '123 Test St. #500'
        ];

        $controller->formatAddress($client);

        $this->assertEquals((object)[
            'address' => '123 Test St. ',
            'streetSecondary' => '#500'
        ], $client);

        $client = (object)[
            'address' => '123 Test St. Suite 500'
        ];

        $controller->formatAddress($client);

        $this->assertEquals((object)[
            'address' => '123 Test St. ',
            'streetSecondary' => 'Suite 500'
        ], $client);
    }

    /**
     * This tests the getBySid method.
     *
     * @return void
     */
    public function testGetBySid(): void
    {
        $this->useDatabase = false; // Nothing to delete on teardown for this test.

        /**
         * DentalQore's profile sid.
         */
        $profileSid = "BU83c76b6f0dcb560047ee0b3a0cfa92f9";
        $controller = new TwilioProfileController();
        $row = $controller->getBySid($profileSid);

        $this->assertEquals($profileSid, $row->profile_sid);
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
            $this->controller->deleteFromDatabase('TwilioProfiles', [
                'app_code' => static::TEST_APP_CODE
            ]);

            $this->controller->deleteTestErrors(static::TEST_APP_CODE, '[{"failure_reason":"Test error."}]');
            $this->controller->deleteTestErrors(static::TEST_APP_CODE, "The client profile was rejected by Twilio.");
            $this->controller->deleteTestErrors(static::TEST_APP_CODE, "No business information found for app code: " . static::TEST_APP_CODE . ".");
            $this->controller->deleteTestErrors(static::TEST_APP_CODE, "No authorized representative found for app code: " . static::TEST_APP_CODE . ".");
            $this->controller->deleteTestErrors(static::TEST_APP_CODE, 'There was an error saving the profile status to the database.');
        }
    }
}