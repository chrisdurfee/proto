<?php declare(strict_types=1);
namespace Tests\Feature;

use App\Models\Patient;

/**
 * PatientTestTrait
 *
 * This trait is used to
 * help with patient tests.
 *
 * @package Tests\Feature\Automation
 */
trait PatientTestTrait
{
	/**
	 * This is the test app code.
	 *
	 * @var string
	 */
	protected string $TEST_APP_CODE = '113307008';

	/**
	 * This is the test patient id.
	 *
	 * This is the pat_id column value
	 * in the patients table.
	 *
	 * @var int
	 */
	protected int $TEST_PATIENT_ID = 9218347;

	/**
	 * This will hold the test patient
	 * after fetched from the db.
	 *
	 * @var object|null
	 */
	protected ?object $testPatient;

	/**
	 * This will get the test patient
	 * from the db.
	 *
	 * @return void
	 */
	protected function getTestPatient(): void
	{
		$patientModel = new Patient();
		$patient = $patientModel->getBy((object)[
			'pat_id' => $this->TEST_PATIENT_ID,
			'app' => $this->TEST_APP_CODE
		]);
		if (!$patient)
		{
			$this->fail("The test patient wasn't found.");
		}
		$this->testPatient = $patient;
	}
}