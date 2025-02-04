<?php declare(strict_types=1);
namespace Tests\Feature\Automation\Patient;

use Core\Automation\Notifier;
use Core\Automation\Patient\Birthday;
use App\Models\Patient;

/**
 * BirthdayTest
 *
 * This will test the Birthday notifier.
 *
 * @package Tests\Feature\Automation\Patient
 */
final class BirthdayTest extends PatientNotifierTestBase
{
    /**
	 * This will get the notifier to test.
	 *
	 * @return Notifier
	 */
	protected function getNotifier(): Notifier
    {
        return new Birthday();
    }

	/**
	 * This will get the model.
	 *
	 * @return object
	 */
	protected function getModel(): object
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

        $todaysDate = date('Y-m-d');
		Patient::edit((object)['id' => $this->testPatient->id, 'dob' => $todaysDate]);
	}

    /**
	 * This will test the send notices method.
	 *
	 * @return void
	 */
	public function testSendNotices(): void
	{
		$response = $this->notifier->sendNotices();

		$this->assertEquals(1, $response->activeClients, "Check that the reminder is active.");
		$this->assertEquals(true, $response->success);
		$this->assertEquals('the notices have been sent', $response->message);
	}
}