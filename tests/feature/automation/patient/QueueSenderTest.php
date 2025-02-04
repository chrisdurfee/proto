<?php declare(strict_types=1);
namespace Tests\Feature\Automation\Patient;

use Tests\Test;
use Core\Automation\Service;
use Core\Models\Queue;
use Tests\Feature\PatientTestTrait;
use Proto\Config;

/**
 * QueueSenderTest
 *
 * This will test the queue sender.
 *
 * @package Tests\Feature\Automation\Patient
 */
class QueueSenderTest extends Test
{
    use PatientTestTrait;

    /**
     * This will be called when the test is set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        /**
		 * Setting the environment to 'dev' so
		 * that the service class can set the testing property.
		 */
		$config = Config::getInstance();
		$config->set('env', 'dev');

        $this->getTestPatient();

        $testMessage = (object)[
            'app' => $this->TEST_APP_CODE,
            'patId' => $this->TEST_PATIENT_ID,
            'messageSendId' => 'test-queue-sender-sms',
            'type' => 'text',
            'sendTo' => $this->testPatient->mobilePhone ?? '',
            'sendFrom' => 'sms@bp1.io',
            'fromName' => 'sms@bp1.io',
            'subject' => 'queue sender subject',
            'message' => 'queue sender test message ampersand &',
            'priority' => 5
        ];
        Queue::push($testMessage);
    }

    /**
     * This is an example bool test.
     *
     * @return void
     */
    public function testProcessQueue(): void
    {
        $service = Service::getService('Patient\QueueSender');
        $service->setTesting(true);
        $result = $service->processQueue();

        $this->assertEquals(1, $result->number);
        $this->assertEquals(true, $result->success);
    }
}