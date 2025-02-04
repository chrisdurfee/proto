<?php declare(strict_types=1);
namespace Tests\Feature\Automation\Patient;

use Tests\Feature\Automation\NotifierTestBase;
use Core\Automation\Notifier;
use Core\Automation\Patient\Campaign;
use Core\Models\Campaign as CampaignModel;

/**
 * CampaignTest
 *
 * This will test the Campaign notifier.
 *
 * @package Tests\Feature\Automation\Patient
 */
final class CampaignTest extends NotifierTestBase
{
    /**
	 * This will get the notifier to test.
	 *
	 * @return Notifier
	 */
	protected function getNotifier(): Notifier
    {
        return new Campaign();
    }

	/**
	 * This will get the model.
	 *
	 * @return object
	 */
	protected function getModel(): object
    {
        return new CampaignModel();
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