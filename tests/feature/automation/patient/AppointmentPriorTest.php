<?php declare(strict_types=1);
namespace Tests\Feature\Automation\Patient;

use Core\Automation\Patient\AppointmentPrior;
use Core\Models\Appointment;
use Core\Models\Client;

/**
 * AppointmentPriorTest
 *
 * This will test the appointment prior notifier.
 *
 * @package Tests\Feature\Automation\Patient
 */
final class AppointmentPriorTest extends AppointmentTestBase
{
	/**
	 * This is the database column name in the client's
	 * table. This is used to determine if the reminder
	 * is currently active or not.
	 *
	 * @var string $reminderName
	 */
	protected string $reminderName = 'appointments';

	/**
	 * This gets the notifier that will be tested.
	 *
	 * @return AppointmentPrior
	 */
	protected function getNotifier(): AppointmentPrior
	{
        $today = date('Y-m-d');
		return new AppointmentPrior($today);
	}

	/**
	 * This gets the model for creating and deleting test appointments.
	 *
	 * @return Appointment
	 */
	protected function getModel(): Appointment
	{
		return new Appointment();
	}

    /**
	 * This will get the appointment date.
	 *
	 * @return string
	 */
	protected function getAppointmentDate(): string
	{
        $clientModel = new Client();
        $testClient = $clientModel->getClient($this->TEST_APP_CODE);
        $textDaysBefore = $testClient->textDay1;

        return date('Y-m-d', strtotime('+' . $textDaysBefore . ' days'));
	}

	/**
	 * This will save test appointments.
	 *
	 * @return void
	 */
	protected function createTestAppointments(): void
	{
		$appointments = [
			$this->createTestAppointment('11:00:00', $this->appointmentDate)
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
			'appointment'
		);
	}
}