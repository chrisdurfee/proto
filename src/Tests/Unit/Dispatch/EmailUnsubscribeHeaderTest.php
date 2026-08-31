<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Dispatch;

use Proto\Dispatch\Controllers\EmailController;
use Proto\Dispatch\Email;
use Proto\Dispatch\Email\Unsubscribe\EmailCategory;
use Proto\Tests\Test;

/**
 * EmailUnsubscribeHeaderTest
 *
 * RFC 8058 headers must land on the PHPMailer instance (the SMTP path
 * never called setupHeader()). Transactional dispatch must not inject a
 * footer unsubscribe URL.
 *
 * @package Proto\Tests\Unit\Dispatch
 */
final class EmailUnsubscribeHeaderTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testApplyUnsubscribeHeadersAddsRfc8058Pair(): void
	{
		$url = 'https://example.com/api/user/unsubscribe?requestId=abc&email=a%40b.com';
		$email = new Email('a@b.com', 'html', 'from@example.com', 'Subject', '<p>Hi</p>');
		$email->setUnsubscribeUrl($url);
		$email->applyUnsubscribeHeaders();

		$mailer = $email->getMailer();
		$this->assertNotNull($mailer);

		$headers = $mailer->getCustomHeaders();
		$byName = [];
		foreach ($headers as $row)
		{
			$byName[$row[0]] = $row[1];
		}

		$this->assertSame('<' . $url . '>', $byName['List-Unsubscribe'] ?? null);
		$this->assertSame('List-Unsubscribe=One-Click', $byName['List-Unsubscribe-Post'] ?? null);
	}

	/**
	 * @return void
	 */
	public function testNoHeadersWhenUnsubscribeUrlIsEmpty(): void
	{
		$email = new Email('a@b.com', 'html', 'from@example.com', 'Subject', '<p>Hi</p>');
		$email->applyUnsubscribeHeaders();

		$mailer = $email->getMailer();
		$this->assertNotNull($mailer);
		$this->assertSame([], $email->getUnsubscribeHeaderPairs());
		$this->assertSame([], $mailer->getCustomHeaders());
	}

	/**
	 * @return void
	 */
	public function testTransactionalDispatchDoesNotInjectFooterUrl(): void
	{
		$data = EmailControllerUnsubscribeHarness::exposeSetEmailData(
			(object)['name' => 'Ada'],
			(object)['to' => 'ada@example.com']
		);

		$this->assertSame('', $data->unsubscribeUrl);
		$this->assertSame('Ada', $data->name);
	}

	/**
	 * @return void
	 */
	public function testExplicitHeaderUrlIsUsedWithoutGeneratingAToken(): void
	{
		$url = 'https://example.com/api/user/unsubscribe?requestId=x&email=ada%40example.com';
		$resolved = EmailControllerUnsubscribeHarness::exposeHeaderUrl((object)[
			'emailCategory' => EmailCategory::TRANSACTIONAL,
			'unsubscribeUrl' => $url,
			'to' => 'ada@example.com'
		]);

		$this->assertSame($url, $resolved);
	}

	/**
	 * Marketing without an explicit URL still wants a generated header
	 * URL. Empty `to` must not invent one.
	 *
	 * @return void
	 */
	public function testMarketingWithoutRecipientHasNoHeaderUrl(): void
	{
		$resolved = EmailControllerUnsubscribeHarness::exposeHeaderUrl((object)[
			'emailCategory' => EmailCategory::MARKETING,
			'to' => ''
		]);

		$this->assertSame('', $resolved);
	}
}

/**
 * Exposes protected EmailController helpers for unit tests.
 */
final class EmailControllerUnsubscribeHarness extends EmailController
{
	/**
	 * @param object|null $data
	 * @param object $settings
	 * @return object
	 */
	public static function exposeSetEmailData(?object $data, object $settings): object
	{
		return self::setEmailData($data, $settings);
	}

	/**
	 * @param object $settings
	 * @return string
	 */
	public static function exposeHeaderUrl(object $settings): string
	{
		return self::resolveHeaderUnsubscribeUrl($settings);
	}
}
