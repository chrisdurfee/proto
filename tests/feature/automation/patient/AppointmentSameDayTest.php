<?php declare(strict_types=1);
namespace Tests\Feature\Automation\Patient;

use Core\Automation\Patient\AppointmentSameDay;

/**
 * AppointmentSameDayTest
 *
 * This will test the appointment same day notifier.
 *
 * @package Tests\Feature\Automation\Patient
 */
final class AppointmentSameDayTest extends AppointmentTestBase
{
	/**
	 * This is the database column name in the client's
	 * table. This is used to determine if the reminder
	 * is currently active or not.
	 *
	 * @var string $reminderName
	 */
	protected string $reminderName = 'textBefore';

	/**
	 * This gets the notifier that will be tested.
	 *
	 * @return AppointmentSameDay
	 */
	protected function getNotifier(): AppointmentSameDay
	{
		return new AppointmentSameDay(static::TEST_DATE, static::TEST_TIME);
	}

	/**
	 * This will save test appointments.
	 *
	 * @return void
	 */
	protected function createTestAppointments(): void
	{
		$appointments = [
			$this->createTestAppointment('12:00:00'),
			$this->createTestAppointment('13:30:00'),
			$this->createTestAppointment('14:00:00')
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
		$this->assertEquals((object)[
			'activeClients' => 1,
			'number' => 3,
			'message' => 'the notices have been sent',
			'success' => true,
		], $response);
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
			'appointment_hour'
		);
	}
}