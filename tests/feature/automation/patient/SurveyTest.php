<?php declare(strict_types=1);
namespace Tests\Feature\Automation\Patient;

use Core\Automation\Notifier;
use Core\Automation\Patient\Survey;

/**
 * SurveyTest
 *
 * This will test the Survey notifier.
 *
 * @package Tests\Feature\Automation\Patient
 */
final class SurveyTest extends AppointmentTestBase
{
    /**
	 * This is the database column name in the client's
	 * table. This is used to determine if the reminder
	 * is currently active or not.
	 *
	 * @var string $reminderName
	 */
	protected string $reminderName = 'surveys';

    /**
	 * This will get the notifier to test.
	 *
	 * @return Notifier
	 */
	protected function getNotifier(): Notifier
    {
        return new Survey();
    }

    /**
	 * This will get the appointment date.
	 * This should be overriden to use a different date.
	 *
	 * @return string
	 */
	protected function getAppointmentDate(): string
	{
        $today = date('Y-m-d');
        return $today;
	}

	/**
	 * This will set up the test.
	 *
	 * @return void
	 */
	public function setUp(): void
	{
		parent::setUp();

        $testAppointment = $this->createTestAppointment('16:00:00', $this->appointmentDate);
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
		$this->assertEquals('the notices have been sent', $response->message, "Check that a survey is active.");
		$this->assertEquals(true, $response->success);
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