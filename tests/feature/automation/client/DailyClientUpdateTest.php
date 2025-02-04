<?php declare(strict_types=1);
namespace Tests\Feature\Automation\Client;

use Tests\Feature\Automation\NotifierTestBase;
use Core\Automation\Notifier;
use Core\Automation\Client\DailyClientUpdate;
use App\Models\Patient;

/**
 * DailyClientUpdateTest
 *
 * This will test the DailyClientUpdate notifier.
 *
 * @package Tests\Feature\Automation\Patient
 */
final class DailyClientUpdateTest extends NotifierTestBase
{
    /**
	 * This will get the notifier to test.
	 *
	 * @return Notifier
	 */
	protected function getNotifier(): Notifier
    {
        return new DailyClientUpdate();
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
    }
}