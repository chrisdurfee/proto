<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Dispatch;

use Proto\Dispatch\Controllers\ApnsController;
use Proto\Dispatch\Response;
use Proto\Tests\Test;

/**
 * ApnsControllerOverrideTest
 *
 * ApnsController called its hooks with `self::`, so an app subclass
 * overriding buildPayload() / send() was silently ignored.
 *
 * @package Proto\Tests\Unit\Dispatch
 */
final class ApnsControllerOverrideTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testEnqueueUsesSubclassPayloadBuilder(): void
	{
		$result = CustomApnsController::enqueue((object)[
			'tokens' => ['abc'],
			'compiledTemplate' => '{"title":"Hi","message":"There"}'
		]);

		$this->assertSame('custom:{"title":"Hi","message":"There"}', $result->message);
	}

	/**
	 * @return void
	 */
	public function testDispatchUsesSubclassSendAndPayloadBuilder(): void
	{
		CustomApnsController::$sent = null;
		$response = CustomApnsController::dispatch((object)[
			'tokens' => ['abc'],
			'compiledTemplate' => '{"title":"Hi"}'
		]);

		$this->assertTrue($response->sent);
		$this->assertNotNull(CustomApnsController::$sent);
		$this->assertSame('custom:{"title":"Hi"}', (fn() => $this->payload)->call(CustomApnsController::$sent));
	}
}

/**
 * App-style subclass overriding the controller hooks.
 */
final class CustomApnsController extends ApnsController
{
	/**
	 * @var object|null
	 */
	public static ?object $sent = null;

	/**
	 * @param string $template
	 * @return string
	 */
	protected static function buildPayload(string $template): string
	{
		return 'custom:' . $template;
	}

	/**
	 * Captures the dispatch instead of contacting APNs.
	 *
	 * @param object $dispatch
	 * @return Response
	 */
	public static function send(object $dispatch): Response
	{
		static::$sent = $dispatch;
		return Response::create(false, 'captured');
	}
}
