<?php declare(strict_types=1);
namespace Tests\Feature\Automation\Patient;

use Core\Automation\Notifier;
use Core\Automation\Patient\Reactivation;
use App\Models\Patient;

/**
 * ReactivationTest
 *
 * This will test the Reactivation notifier.
 *
 * @package Tests\Feature\Automation\Patient
 */
final class ReactivationTest extends PatientNotifierTestBase
{
    /**
	 * This will get the notifier to test.
	 *
	 * @return Notifier
	 */
	protected function getNotifier(): Notifier
    {
        return new Reactivation();
    }

	/**
	 * This will get the model.
	 *
	 * @return ?object
	 */
	protected function getModel(): ?object
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

        $todayLastYear = date('Y-m-d', strtotime('-1 year'));
		Patient::edit((object)[
            'id' => $this->testPatient->id,
            'lastAppointment' => $todayLastYear,
            'status' => 3
        ]);
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

    /**
     * This will be called after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Patient::edit((object)[
            'id' => $this->testPatient->id,
            'status' => 1
        ]);
    }
}