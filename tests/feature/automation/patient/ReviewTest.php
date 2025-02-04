<?php declare(strict_types=1);
namespace Tests\Feature\Automation\Patient;

use Core\Automation\Notifier;
use Core\Automation\Patient\Review;

/**
 * ReviewTest
 *
 * This will test the Review notifier.
 *
 * @package Tests\Feature\Automation\Patient
 */
final class ReviewTest extends AppointmentTestBase
{
    /**
	 * This is the database column name in the client's
	 * table. This is used to determine if the reminder
	 * is currently active or not.
	 *
	 * @var string $reminderName
	 */
	protected string $reminderName = 'reviews';

    /**
	 * This will get the notifier to test.
	 *
	 * @return Notifier
	 */
	protected function getNotifier(): Notifier
    {
        return new Review();
    }

    /**
	 * This will get the appointment date.
	 *
	 * @return string
	 */
	protected function getAppointmentDate(): string
	{
        $yesterday = date('Y-m-d', strtotime('-1 day'));
		return $yesterday;
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
		 * Need to make sure there
		 * aren't any review action logs
		 * for our test patient so the review
		 * notifier will send the notice.
		 */
		$this->model->deletePatActionLogs(
			$this->TEST_PATIENT_ID,
			'review'
		);

        $testAppointment = $this->createTestAppointment('10:00:00', $this->appointmentDate);
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
		$this->model->deleteTestActionLogs(
			$this->TEST_PATIENT_ID,
			'review'
		);
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