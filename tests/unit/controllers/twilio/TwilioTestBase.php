<?php declare(strict_types=1);
namespace Tests\Unit\Controllers\Twilio;

use Tests\Test;
use App\Controllers\Sms\TwilioController;
use App\Controllers\Sms\Twilio\TwilioControllerInterface;
use App\Models\Twilio\TwilioError;
use Tests\MockTrait;
use Tests\Unit\Controllers\Twilio\Dto\BasicTestSettings;
use Tests\DependencyInjector;

/**
 * TwilioTestBase
 *
 * This is the base twilio test class.
 *
 * @package Tests\Unit\Controllers\Twilio
 */
abstract class TwilioTestBase extends Test
{
    /**
     * This will add additional methods to help work with mock objects.
     */
    use MockTrait;

    /**
     * This is the class that will be tested.
     *
     * @var object $controller // Marked as an object to resolve inetelephense errors.
     */
    protected TwilioControllerInterface $controller;

    /**
     * This is the qulified name of the controller class that will be tested.
     *
     * @var string $controllerName
     */
    protected string $controllerName = TwilioController::class;

    /**
     * This is the qulified name of the api class used by the controller.
     *
     * @var string $apiName
     */
    protected string $apiName = '';

    /**
     * This is the qulified name of the model class used by the controller.
     *
     * @var string $modelName
     */
    protected string $modelName = '';

    /**
     * This is the client mock object
     *
     * @var object $clientMock
     */
    protected object $clientMock;

    /**
     * If true, values from tests will be saved to the database.
     * If false, the models will be mocked and nothing will be saved or deleted from the database.
     *
     * Tests run significantly faster when mocking the models. But it's useful to run the tests with
     * the database from time to time to make sure the real models are working correctly as well.
     *
     * @var bool $useDatabase
     */
    protected bool $useDatabase = false;

    /**
     * If true, data from tests will be deleted from the database.
     *
     * @var bool $deleteData
     */
    protected bool $deleteData = true;

    /**
     * If this is set to true, the Twilio API's will be used for each test.
     * Otherwise the Twilio API's will be mocked.
     *
     * Only some tests can successfully use the actual api's with our dummy data.
     *
     * @var bool $useApi
     */
    protected bool $useApi = false;

    /**
     * This is the app code used for testing.
     *
     * This app code is currently tied to a test client in the database.
     *
     * @var string
     */
    protected const TEST_APP_CODE = '417085113';

    /**
     * This will set up the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->clientMock = (object)[
            'app' => static::TEST_APP_CODE,
            'practice' => 'Test Practice',
            'email' => 'testpractice@testpractice.com, anotheremail@testpractice.com',
            'id' => 2841,
            'provisionedPhone' => '1234567890',
            'mobile' => '1234567890',
            'phone' => '1234567890',
            'contactName' => 'Test',
            'facebook' => '',
            'twitter' => 'twitter.com',
            'youtube' => '',
            'instagram' => 'instagram.com',
            'pintrest' => 'pinterest.com',
            'google' => '',
            'yelp' => 'yelp.com',
            'site' => 'test.com',
            'state' => 'montana',
            'address' => '123 Test St'
        ];
    }

    /**
     * This will setup a basic test outline that most of the methods in this class will use.
     *
     * @param BasicTestSettings $settings
     * @param bool $errorTest
     */
    protected function basicTest(BasicTestSettings $settings, bool $errorTest = false): void
    {
        /**
         * This will extract the properties of the $settings object
         * and assign them to variables with the same name.
         */
        extract(get_object_vars($settings));

        $apiClass = $apiClass ?? $this->apiName;
        $apiMock = $this->createCustomMock($apiClass, [$apiMethod]);
        $apiMock->expects($this->once())->method($apiMethod)->willReturn($apiMethodReturn);

        /**
         * Passing in a model to mock is optional.
         */
        $modelClass = $modelClass ?? $this->modelName;
        if (isset($modelClass) && isset($modelMethod))
        {
            $modelMock = $this->createCustomMock($modelClass, [$modelMethod]);
            $modelMock->method($modelMethod)->willReturn($modelMethodReturn ?? true);
        }

        /**
         * This will create the controller object with the dependencies passed in.
         */
        $depends = ['api' => $apiMock];
        if ($errorTest === false)
        {
            $depends['model'] = ($this->useDatabase) ? new $modelClass() : $modelMock;
        }
        $this->controller = DependencyInjector::convert($this->controllerName, $depends);

        /**
         * Setting the client object for the controller.
         */
        $this->controller->setClient($this->clientMock);

        /**
         * Passing in an error model to mock is optional.
         */
        if ($errorTest === true)
        {
            $errorModelMock = $this->createCustomMock($errorModelClass, [$modelMethod]);
            $errorModelMock->method($modelMethod)->willReturn($errorModelMethodReturn ?? true);
            $this->controller->setErrorModel($errorModelMock);
        }

        /**
         * Most controller methods are the same as the api method,
         * so they don't need to explicitly be set.
         */
        if (!isset($controllerMethod))
        {
            $controllerMethod = $apiMethod;
        }

        if ($controllerMethodArguments)
        {
            $response = $this->controller->$controllerMethod(...$controllerMethodArguments);
        }
        else
        {
            $response = $this->controller->$controllerMethod();
        }

        /*
         * If responseType is set, set the success property on the response object.
         * For succesful outcomes, the response object will usually be the same as the apiMethodReturn
         * with a success property appended to it. That's when the responseType property can be used.
         *
         */
        if (isset($responseType) && $responseType === 'success')
        {
            $controllerResponseEquals = $apiMethodReturn;
            $controllerResponseEquals->success = true;
        }

        $this->assertEquals($controllerResponseEquals, $response);
    }

    /**
     * This will create a basic error test.
     *
     * @param BasicTestSettings $settings
     */
    protected function basicErrorTest(BasicTestSettings $settings): void
    {
        $this->basicTest(new BasicTestSettings(
            apiClass: $settings->apiClass ?? null,
            apiMethod: $settings->apiMethod,
            apiMethodReturn: (object)[
                // Actual responses will contain more information, this is all that's necessary for the test //
                'message' => 'Test error.',
                'code' => 400
            ],
            errorModelClass: TwilioError::class,
            modelMethod: 'add',
            controllerMethod: $settings->controllerMethod ?? null,
            controllerMethodArguments: $settings->controllerMethodArguments ?? null,
            controllerResponseEquals: $settings->controllerResponseEquals ?? (object)[
                'message' => 'Test error. Code: 400.',
                'success' => false
            ]
        ), true);
    }

    /**
     * This will instantiate the controller with the dependencies passed in.
     *
     * @param object|null $modelMock
     * @param object|null $apiMock
     * @param object|null $errorMock
     * @return object
     */
    protected function createController(
        ?object $modelMock = null,
        ?object $apiMock = null,
        ?object $errorMock = null
    ): object
    {
        $depends = [];
        if ($modelMock)
        {
            $depends['model'] = $this->useDatabase ? new $this->modelName() : $modelMock;
        }
        if ($apiMock)
        {
            $depends['api'] = $apiMock;
        }

        $controller = DependencyInjector::convert($this->controllerName, $depends);
        $controller->setClient($this->clientMock);

        if ($errorMock)
        {
            $errorModel = $this->useDatabase ? new TwilioError() : $errorMock;
            $controller->setErrorModel($errorModel);
        }

        return $controller;
    }

    /**
     * This will create a simple mock object
     * with one mocked method.
     *
     * @param string $type
     * @param string $method
     * @param mixed $willReturn
     * @return object
     */
    protected function createSimpleMock(string $type, string $method, mixed $willReturn): object
    {
        $className = match ($type)
        {
            'api' => $this->apiName,
            'model' => $this->modelName,
            'controller' => $this->controllerName,
            'errorModel' => TwilioError::class,
            'error' => TwilioError::class,
            default => $type // Used for custom classnames
        };

        $mock = $this->createCustomMock($className, [$method]);
        if ($type === 'api')
        {
            $mock->expects($this->once())->method($method)->willReturn($willReturn);
            return $mock;
        }

        $mock->method($method)->willReturn($willReturn);
        return $mock;
    }
}