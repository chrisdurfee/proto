<?php declare(strict_types=1);
namespace Proto\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use Proto\Validation\PasswordPolicy;

/**
 * PasswordPolicyTest
 *
 * @package Proto\Tests\Unit\Validation
 */
class PasswordPolicyTest extends TestCase
{
	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		PasswordPolicy::reset();
	}

	/**
	 * @return void
	 */
	public function testStrongPasswordPasses(): void
	{
		$this->assertTrue(PasswordPolicy::isStrong('GoodPass123!'));
	}

	/**
	 * @return void
	 */
	public function testShortPasswordFails(): void
	{
		$this->assertFalse(PasswordPolicy::isStrong('Short1!'));
	}

	/**
	 * @return void
	 */
	public function testNameFragmentFails(): void
	{
		$this->assertFalse(PasswordPolicy::isStrong('ChrisPass123!', 'Christopher', 'Durfee'));
	}

	/**
	 * @return void
	 */
	public function testConfigureRaisesMinLength(): void
	{
		PasswordPolicy::configure(['minLength' => 20]);
		$this->assertFalse(PasswordPolicy::isStrong('GoodPass123!'));
		$this->assertTrue(PasswordPolicy::isStrong('GoodPass123!ExtraLong'));
	}
}
