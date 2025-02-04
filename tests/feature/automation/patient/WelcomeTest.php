<?php declare(strict_types=1);
namespace Tests\Feature\Automation\Patient;

use Core\Automation\Notifier;
use Core\Automation\Patient\Welcome;
use App\Models\Patient;

/**
 * WelcomeTest
 *
 * This will test the Welcome notifier.
 *
 * @package Tests\Feature\Automation\Patient
 */
final class WelcomeTest extends PatientNotifierTestBase
{
    /**
	 * This will get the notifier to test.
	 *
	 * @return Notifier
	 */
	protected function getNotifier(): Notifier
    {
        return new Welcome();
    }

	/**
	 * This will get the model.
	 *
	 * @return object
	 */
	protected function getModel(): object
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

        /**
         * Setting patient setup to yesterday
         * so the welcome will be sent.
         */
        $yesterday = date('Y-m-d', strtotime('-1 day'));
		Patient::edit((object)[
            'id' => $this->testPatient->id,
            'date_setup'=> $yesterday
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
        /**
         * Reverting date setup to
         * original as it was in the database.
         */
        Patient::edit((object)[
            'id' => $this->testPatient->id,
            'date_setup'=> '2014-02-26'
        ]);
    }
}