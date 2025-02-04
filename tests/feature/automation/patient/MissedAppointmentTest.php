<?php declare(strict_types=1);
namespace Tests\Feature\Automation\Patient;

use Core\Automation\Patient\MissedAppointment;

/**
 * MissedAppointmentTest
 *
 * This will test the missed appointment notifier.
 *
 * @package Tests\Feature\Automation\Patient
 */
final class MissedAppointmentTest extends AppointmentTestBase
{
	/**
	 * This is the database column name in the client's
	 * table. This is used to determine if the reminder
	 * is currently active or not.
	 *
	 * @var string $reminderName
	 */
	protected string $reminderName = 'missedAppointments';

    /**
     * This is the test appointment date.
	 *
	 * @var string $appointmentDate
     */
    protected string $appointmentDate;

	/**
	 * This gets the notifier that will be tested.
	 *
	 * @return MissedAppointment
	 */
	protected function getNotifier(): MissedAppointment
	{
        $today = date('Y-m-d');
		return new MissedAppointment($today);
	}

    /**
	 * This will get the appointment date.
	 *
	 * @return string
	 */
	protected function getAppointmentDate(): string
	{
        return date('Y-m-d', strtotime('-1 day'));
	}

	/**
	 * This will save test appointments.
	 *
	 * @return void
	 */
	protected function createTestAppointments(): void
	{
        $appointment = $this->createTestAppointment(
            '07:00:00',
            $this->appointmentDate
        );
        $appointment->broken = 1;

		$appointments = [
			$appointment
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
		$this->assertEquals(
			(object)[
				'activeClients' => 1,
				'number' => 1,
				'message' => 'the notices have been sent',
				'success' => true,
			],
			$response,
			"The test patient may have future appointments set up."
		);
	}

    /**
	 * This will delete the test appointments.
	 *
	 * @return void
	 */
	protected function deleteTestAppointments(): void
	{
		$this->model->deleteTestAppointments('feature test');
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
			'missed_appointment'
		);
	}
}