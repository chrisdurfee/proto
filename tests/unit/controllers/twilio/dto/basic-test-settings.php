<?php declare(strict_types=1);
namespace Tests\Unit\Controllers\Twilio\Dto;

/**
 * BasicTestSettings
 *
 * This is a data transfer object that will be used to pass
 * the settings for the basicTest method in the TwilioBaseTest class.
 *
 * @package Tests\Unit\Controllers\Twilio\Dto
 */
class BasicTestSettings
{
    /**
     * This is the constructor method for the BasicTestSettings class.
     *
     * @param string|null $apiClass
     * @param string|null $apiMethod
     * @param mixed $apiMethodReturn
     * @param string|null $apiType
     * @param string|null $controllerMethod
     * @param array|null $controllerMethodArguments
     * @param mixed $controllerResponseEquals
     * @param string|null $responseType
     * @param string|null $modelClass
     * @param string|null $modelType
     * @param string|null $modelMethod
     * @param mixed $modelMethodReturn
     * @param string|null $errorModelClass
     */
    public function __construct(
        public ?string $apiClass = null,
        public ?string $apiMethod = null,
        public mixed $apiMethodReturn = null,
        public ?string $apiType = null,
        public ?string $controllerMethod = null,
        public ?array $controllerMethodArguments = null,
        public mixed $controllerResponseEquals = null,
        public ?string $responseType = null,
        public ?string $modelClass = null,
        public ?string $modelType = null,
        public ?string $modelMethod = null,
        public mixed $modelMethodReturn = null,
        public ?string $errorModelClass = null
    ){}
}