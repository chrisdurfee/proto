<?php declare(strict_types=1);
namespace Tests\Feature\Automation\Patient;

use Core\Models\Appointment;
use Core\Models\Client;

/**
 * AppointmentTestBase
 *
 * This is the base appointment test class.
 *
 * @package Tests\Feature\Automation\Patient
 * @abstract
 */
abstract class AppointmentTestBase extends PatientNotifierTestBase
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
	 * This is used to store whether the reminder
	 * is currently enabled or not.
	 *
	 * @var bool $reminderEnabled
	 */
	protected bool $reminderEnabled = true;

	/**
     * This is the test appointment date.
	 *
	 * @var string $appointmentDate
     */
    protected string $appointmentDate;

	/**
	 * This will set up the test.
	 *
	 * @return void
	 */
	public function setUp(): void
	{
		parent::setUp();

		$this->reminderEnabled = $this->getReminderValue();
		$this->appointmentDate = $this->getAppointmentDate();
	}

	/**
	 * This will check if the reminder is enabled.
	 *
	 * @return bool
	 */
	protected function getReminderValue(): bool
	{
		$clientModel = new Client();
		$client = $clientModel->getClient($this->TEST_APP_CODE);

		return (bool)$client->{$this->reminderName};
	}

	/**
	 * This will cause the test to fail
	 * if the reminder isn't enabled.
	 *
	 * @return void
	 */
	protected function checkReminder(): void
	{
		if ($this->reminderEnabled === false)
		{
			$this->fail("The {$this->reminderName} reminder is not enabled.");
		}
	}

	/**
	 * This gets the model.
	 *
	 * @return Appointment
	 */
	protected function getModel(): Appointment
	{
		return new Appointment();
	}

	/**
	 * This will get the appointment date.
	 * This should be overriden to use a different date.
	 *
	 * @return string
	 */
	protected function getAppointmentDate(): string
	{
		return date('Y-m-d');
	}

    /**
	 * This will create a test appointment object.
	 *
	 * @param string $time
	 * @param string|null $date
	 * @return object
	 */
	protected function createTestAppointment(
		string $time,
		?string $date = null
	): object
	{
		$timeMeasures = explode(':', $time);
		$hour = $timeMeasures[0];
		$minute = $timeMeasures[1];

		return (object)[
			'app' => $this->TEST_APP_CODE,
			'appointmentId' => null,
			'date' => $date ?? static::TEST_DATE,
			'time' => $time,
			'patID' => $this->TEST_PATIENT_ID,
			'length' => null,
			'hour' => $hour,
			'minute' => $minute,
			'broken' => 0,
			'completed' => 0,
			'confirmation' => 0,
			'reason' => 'feature test',
			'note' => 'feature test',
			'providerID' => '',
			'operatoryID' => 'xh2-pyt',
			'amount' => '',
			'firstName' => $this->testPatient->firstName ?? '',
			'lastName' => $this->testPatient->lastName ?? '',
			'email' => $this->testPatient->email ?? '',
			'mobile' => $this->testPatient->mobilePhone ?? '',
			'statusID' => 0
		];
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
	abstract protected function deleteTestActionLogs(): void;

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