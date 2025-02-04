<?php declare(strict_types=1);
namespace Tests\Feature\Automation\Patient;

use Tests\Feature\Automation\NotifierTestBase;
use Core\Automation\Notifier;
use Core\Automation\Patient\Holiday;

/**
 * HolidayTest
 *
 * This will test the Holiday notifier.
 *
 * @package Tests\Feature\Automation\Patient
 */
final class HolidayTest extends NotifierTestBase
{
    /**
	 * This will get the notifier to test.
	 *
	 * @return Notifier
	 */
	protected function getNotifier(): Notifier
    {
        $fourthOfJuly = date('Y-07-04');
        return new Holiday($fourthOfJuly);
    }

	/**
	 * This will get the model.
	 *
	 * @return ?object
	 */
	protected function getModel(): ?object
    {
        return null;
    }

	/**
	 * This will set up the test.
	 *
	 * @return void
	 */
	public function setUp(): void
	{
		parent::setUp();
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
}