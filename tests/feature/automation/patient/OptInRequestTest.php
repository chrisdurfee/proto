<?php declare(strict_types=1);
namespace Tests\Feature\Automation\Patient;

use Tests\Feature\Automation\NotifierTestBase;
use Core\Automation\Notifier;
use Core\Automation\Patient\OptInRequest;
use Core\Models\Patient;
use Core\Models\Model;
use App\Models\PatientOptIn;

/**
 * OptInRequestTest
 *
 * This will test the OptInRequest notifier.
 *
 * @package Tests\Feature\Automation\Patient
 */
final class OptInRequestTest extends PatientNotifierTestBase
{
    /**
	 * This will get the notifier to test.
	 *
	 * @return Notifier
	 */
	protected function getNotifier(): Notifier
    {
        return new OptInRequest();
    }

	/**
	 * This will get the model.
	 *
	 * @return Model
	 */
	protected function getModel(): Model
    {
        return new Patient();
    }

	/**
	 * This will set up the test.
	 *
	 * @return void
	 */
	public function setUp(): void
	{
		parent::setUp();

		PatientOptIn::remove((object)['patId' => $this->testPatient->id]);
	}

    /**
	 * This will test the send notices method.
	 *
	 * @return void
	 */
	public function testSendNotices(): void
	{
		$response = $this->notifier->sendNotices();

		$this->assertEquals(1, $response->number);
		$this->assertEquals(true, $response->success);
		$this->assertEquals('the notices have been sent', $response->message);
	}
}