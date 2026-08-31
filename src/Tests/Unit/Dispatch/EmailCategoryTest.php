<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Dispatch;

use Proto\Dispatch\Email\Unsubscribe\EmailCategory;
use Proto\Tests\Test;

/**
 * EmailCategoryTest
 *
 * Promotional mail may carry one-click unsubscribe. Everything else
 * defaults to transactional so reset/MFA/invoices stay header-light.
 *
 * @package Proto\Tests\Unit\Dispatch
 */
final class EmailCategoryTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testUnknownAndEmptyNormalizeToTransactional(): void
	{
		$this->assertSame(EmailCategory::TRANSACTIONAL, EmailCategory::normalize(null));
		$this->assertSame(EmailCategory::TRANSACTIONAL, EmailCategory::normalize(''));
		$this->assertSame(EmailCategory::TRANSACTIONAL, EmailCategory::normalize('promo'));
	}

	/**
	 * @return void
	 */
	public function testKnownCategoriesNormalizeCaseInsensitively(): void
	{
		$this->assertSame(EmailCategory::MARKETING, EmailCategory::normalize('Marketing'));
		$this->assertSame(EmailCategory::DIGEST, EmailCategory::normalize('digest'));
		$this->assertSame(EmailCategory::INTERNAL, EmailCategory::normalize('INTERNAL'));
	}

	/**
	 * @return void
	 */
	public function testOmittedCategoryDoesNotWantUnsubscribe(): void
	{
		$this->assertFalse(EmailCategory::wantsUnsubscribe((object)['to' => 'a@b.com']));
		$this->assertFalse(EmailCategory::wantsUnsubscribe((object)[
			'emailCategory' => EmailCategory::TRANSACTIONAL
		]));
		$this->assertFalse(EmailCategory::wantsUnsubscribe((object)[
			'emailCategory' => EmailCategory::INTERNAL
		]));
	}

	/**
	 * @return void
	 */
	public function testPromotionalCategoriesWantUnsubscribe(): void
	{
		$this->assertTrue(EmailCategory::wantsUnsubscribe((object)[
			'emailCategory' => EmailCategory::MARKETING
		]));
		$this->assertTrue(EmailCategory::wantsUnsubscribe((object)[
			'emailCategory' => EmailCategory::DIGEST
		]));
	}

	/**
	 * An explicit URL always opts in, including on transactional mail
	 * (tests and callers that already built a token).
	 *
	 * @return void
	 */
	public function testExplicitUnsubscribeUrlWins(): void
	{
		$this->assertTrue(EmailCategory::wantsUnsubscribe((object)[
			'emailCategory' => EmailCategory::TRANSACTIONAL,
			'unsubscribeUrl' => 'https://example.com/unsub'
		]));
	}
}
