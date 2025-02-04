<?php declare(strict_types=1);
namespace Tests\Feature\Automation\Patient;

use Core\Automation\Notifier;
use Core\Automation\Patient\Recare;
use Core\Models\Client;

/**
 * RecareTest
 *
 * This will test the Recare notifier.
 *
 * @package Tests\Feature\Automation\Patient
 */
final class RecareTest extends AppointmentTestBase
{
    /**
	 * This is the database column name in the client's
	 * table. This is used to determine if the reminder
	 * is currently active or not.
	 *
	 * @var string $reminderName
	 */
	protected string $reminderName = 'recare';

    /**
	 * This will get the notifier to test.
	 *
	 * @return Notifier
	 */
	protected function getNotifier(): Notifier
    {
        return new Recare();
    }

    /**
	 * This will get the appointment date.
	 * This should be overriden to use a different date.
	 *
	 * @return string
	 */
	protected function getAppointmentDate(): string
	{
        $model = new Client();
        $dqClient = $model->getClient($this->TEST_APP_CODE);
        $recareInterval = $dqClient->recareInterval;

        $intervalMonthsAgo = date('Y-m-d', strtotime("-$recareInterval months"));

		return $intervalMonthsAgo;
	}

	/**
	 * This will set up the test.
	 *
	 * @return void
	 */
	public function setUp(): void
	{
		parent::setUp();

		/**
		 * All appointments for this patient need to be deleted
		 * so the recare reminder will be sent.
		 */
		$this->model->deletePatientAppointments('4444', '113307008');

        $testAppointment = $this->createTestAppointment('12:00:00', $this->appointmentDate);
        $this->model->saveAppointments([$testAppointment]);
	}

    /**
	 * This will test the send notices method.
	 *
	 * @return void
	 */
	public function testSendNotices(): void
	{
        $this->checkReminder();

		$response = $this->notifier->sendNotices();

		$this->assertEquals(1, $response->activeClients, "Check that the reminder is active.");
		$this->assertEquals(true, $response->success);
		$this->assertEquals('the notices have been sent', $response->message);
	}

    /**
	 * This will delete the test action logs.
	 *
	 * @return void
	 */
	protected function deleteTestActionLogs(): void
    {
        return;
    }

    /**
     * This will be called after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->deleteTestAppointments();
		$this->deleteTestActionLogs();
    }
}