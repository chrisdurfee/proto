<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Dispatch;

use GuzzleHttp\Psr7\Request;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\SubscriptionInterface;
use Minishlink\WebPush\WebPush as MinishlinkWebPush;
use Proto\Dispatch\WebPush;
use Proto\Tests\Test;

/**
 * WebPushSendTest
 *
 * WebPush::send() passed the success flag as Response::create()'s
 * *error* argument, so delivered pushes reported `sent=false` and
 * failed pushes reported `sent=true`.
 *
 * @package Proto\Tests\Unit\Dispatch
 */
final class WebPushSendTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		FakeWebPushDispatch::reset();
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testSuccessfulBatchReportsSent(): void
	{
		$response = FakeWebPushDispatch::withReports([true, true])->send();

		$this->assertTrue($response->sent);
		$this->assertTrue($response->success);
		$this->assertFalse($response->error);
		$this->assertCount(2, $response->getData());
	}

	/**
	 * @return void
	 */
	public function testFailedDeliveryReportsError(): void
	{
		$response = FakeWebPushDispatch::withReports([true, false])->send();

		$this->assertFalse($response->sent);
		$this->assertFalse($response->success);
		$this->assertTrue($response->error);
	}
}

/**
 * WebPush dispatch with a fake transport.
 */
final class FakeWebPushDispatch extends WebPush
{
	/**
	 * Builds a dispatch whose flush yields one report per flag.
	 *
	 * @param array $results
	 * @return self
	 */
	public static function withReports(array $results): self
	{
		static::$webPush = new FakeMinishlinkWebPush($results);

		$subscriptions = [];
		foreach ($results as $i => $_)
		{
			$subscriptions[] = [
				'endpoint' => 'https://push.example.com/' . $i,
				'keys' => ['auth' => 'a', 'p256dh' => 'b']
			];
		}
		return new self($subscriptions, '{}');
	}

	/**
	 * Clears the shared transport.
	 *
	 * @return void
	 */
	public static function reset(): void
	{
		static::$webPush = null;
	}

	/**
	 * Skips building a real transport (the fake is preinstalled).
	 *
	 * @param array $subscription
	 * @return \Minishlink\WebPush\Subscription|null
	 */
	protected function setupSubscription(array $subscription = []): ?\Minishlink\WebPush\Subscription
	{
		return new \Minishlink\WebPush\Subscription($subscription['endpoint']);
	}
}

/**
 * Minishlink transport double that never touches the network.
 */
final class FakeMinishlinkWebPush extends MinishlinkWebPush
{
	/**
	 * @var array
	 */
	private array $endpoints = [];

	/**
	 * @param array $results
	 */
	public function __construct(private array $results)
	{
	}

	/**
	 * @param SubscriptionInterface $subscription
	 * @param string|null $payload
	 * @param array $options
	 * @param array $auth
	 * @return void
	 */
	public function queueNotification(SubscriptionInterface $subscription, ?string $payload = null, array $options = [], array $auth = []): void
	{
		$this->endpoints[] = $subscription->getEndpoint();
	}

	/**
	 * @param int|null $batchSize
	 * @return \Generator
	 */
	public function flush(?int $batchSize = null): \Generator
	{
		foreach ($this->endpoints as $i => $endpoint)
		{
			yield new MessageSentReport(new Request('POST', $endpoint), null, (bool)($this->results[$i] ?? false));
		}
		$this->endpoints = [];
	}
}
