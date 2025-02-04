<?php declare(strict_types=1);
namespace Tests\Feature\Automation\Patient;

use Core\Automation\Patient\AppointmentScheduled;
use App\Models\AppointmentSync;

/**
 * AppointmentScheduledTest
 *
 * This will test the appointment scheduled notifier.
 *
 * @package Tests\Feature\Automation\Patient
 */
final class AppointmentScheduledTest extends AppointmentTestBase
{
	/**
	 * This is the database column name in the client's
	 * table. This is used to determine if the reminder
	 * is currently active or not.
	 *
	 * @var string $reminderName
	 */
	protected string $reminderName = 'scheduling';

	/**
	 * This gets the notifier that will be tested.
	 *
	 * @return AppointmentScheduled
	 */
	protected function getNotifier(): AppointmentScheduled
	{
		/**
		 * The sync model can be mocked if needed.
		 */
		/** @var AppointmentSync $mockSyncModel */
		// $mockSyncModel = $this->createMock(AppointmentSync::class);
		// $lastSyncDate = date("Y-m-d 6:00:00", strtotime("-1 day"));
		// $mockSyncModel->method('lastSyncTime')->willReturn($lastSyncDate);

		return new AppointmentScheduled(
			date('Y-m-d'),
			static::TEST_TIME,
			// $mockSyncModel
		);
	}

	/**
	 * This will get the test appointment date.
	 *
	 * @return string
	 */
	protected function getAppointmentDate(): string
	{
		return date("Y-m-d", strtotime("+1 day"));
	}

	/**
	 * This will save test appointments.
	 *
	 * @return void
	 */
	protected function createTestAppointments(): void
	{
		$appointments = [
			$this->createTestAppointment('14:00:00', $this->appointmentDate)
		];

		$this->model->saveAppointments($appointments);
	}

	/**
	 * This will test the send notices method.
	 *
	 * @return void
	 */
	public function testSendNotices(): void
	{
		$this->checkReminder();
		$this->createTestAppointments();

		$response = $this->notifier->sendNotices();
		$this->assertEquals(true, $response->success);
		$this->assertEquals('the notices have been sent', $response->message);
	}

	/**
	 * This will delete the test appointments.
	 *
	 * @return void
	 */
	protected function deleteTestAppointments(): void
	{
		$this->model->deleteTestAppointments(
			'feature test'
		);
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
			'appointment_scheduled'
		);
	}
}