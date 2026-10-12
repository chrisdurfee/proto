<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Dispatch;

use Proto\Dispatch\Email;
use Proto\Tests\Test;

/**
 * EmailTimeoutTest
 *
 * PHPMailer's default SMTP timeout is 300 seconds; a dead relay could
 * pin a request or queue worker for five minutes per message.
 *
 * @package Proto\Tests\Unit\Dispatch
 */
final class EmailTimeoutTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testMailerUsesBoundedDefaultTimeout(): void
	{
		$email = new Email('a@b.com', 'html', 'from@example.com', 'Subject', '<p>Hi</p>');

		$this->assertSame(Email::DEFAULT_TIMEOUT, $email->getMailer()->Timeout);
		$this->assertLessThan(300, $email->getMailer()->Timeout);
	}

	/**
	 * @return void
	 */
	public function testTimeoutIsConfigurable(): void
	{
		$this->assertSame(42, EmailTimeoutHarness::timeoutFor((object)['timeout' => 42]));
		$this->assertSame(Email::DEFAULT_TIMEOUT, EmailTimeoutHarness::timeoutFor((object)['timeout' => 0]));
		$this->assertSame(Email::DEFAULT_TIMEOUT, EmailTimeoutHarness::timeoutFor((object)[]));
	}
}

/**
 * Exposes Email::resolveTimeout() for tests.
 */
final class EmailTimeoutHarness extends Email
{
	/**
	 * @param object $settings
	 * @return int
	 */
	public static function timeoutFor(object $settings): int
	{
		return static::resolveTimeout($settings);
	}
}
