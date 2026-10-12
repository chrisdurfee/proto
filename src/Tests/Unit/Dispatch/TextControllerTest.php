<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Dispatch;

use Proto\Dispatch\Controllers\TextController;
use Proto\Dispatch\Response;
use Proto\Tests\Test;

/**
 * TextControllerTest
 *
 * TextController only honoured `compiledTemplate` when a subject was
 * also set, so a queued SMS (whose stored body arrives as
 * `compiledTemplate`) could be re-rendered from `template` or sent
 * with no body at all.
 *
 * @package Proto\Tests\Unit\Dispatch
 */
final class TextControllerTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testStoredBodyIsSentWithoutSubject(): void
	{
		$driver = new CapturingTextDriver();
		$response = TextController::dispatch((object)[
			'to' => '+15550001111',
			'session' => 'test',
			'compiledTemplate' => 'Stored queue body',
			'customDriver' => $driver
		]);

		$this->assertTrue($response->sent);
		$this->assertSame('Stored queue body', $driver->settings->message);
	}

	/**
	 * @return void
	 */
	public function testStoredBodyWinsOverTemplate(): void
	{
		$driver = new CapturingTextDriver();
		TextController::dispatch((object)[
			'to' => '+15550001111',
			'session' => 'test',
			'template' => 'Some\\Template\\That\\Does\\Not\\Exist',
			'compiledTemplate' => 'Stored queue body',
			'customDriver' => $driver
		]);

		$this->assertSame('Stored queue body', $driver->settings->message);
	}

	/**
	 * @return void
	 */
	public function testEnqueueStoresPrecompiledBodyWithSubjectPrefix(): void
	{
		$row = TextController::enqueue((object)[
			'to' => '+15550001111',
			'subject' => 'Alert',
			'compiledTemplate' => 'Body'
		]);

		$this->assertSame('Alert - Body', $row->message);
		$this->assertSame('+15550001111', $row->recipient);
		$this->assertNull($row->session);
	}
}

/**
 * SMS driver double that records what it was asked to send.
 */
final class CapturingTextDriver
{
	/**
	 * @var object|null
	 */
	public ?object $settings = null;

	/**
	 * @param object $settings
	 * @return Response
	 */
	public function send(object $settings): Response
	{
		$this->settings = clone $settings;
		return Response::create(false, 'captured');
	}
}
