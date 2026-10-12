<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Utils;

use Proto\Utils\Filter\Sanitize;
use Proto\Utils\Filter\Validate;
use Proto\Tests\Test;

/**
 * FilterValidateSanitizeTest
 *
 * Direct coverage of the Validate/Sanitize primitives the Validator
 * dispatches to.
 *
 * @package Proto\Tests\Unit\Utils
 */
final class FilterValidateSanitizeTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	public function testValidateIntAndFloat(): void
	{
		$this->assertTrue(Validate::int('42'));
		$this->assertTrue(Validate::int(-3));
		$this->assertFalse(Validate::int('4.2'));
		$this->assertFalse(Validate::int(null));
		$this->assertTrue(Validate::float('4.2'));
		$this->assertFalse(Validate::float('abc'));
	}

	/**
	 * @return void
	 */
	public function testValidateStringRejectsEmpty(): void
	{
		$this->assertTrue(Validate::string('a'));
		$this->assertFalse(Validate::string(''));
		$this->assertFalse(Validate::string(null));
	}

	/**
	 * @return void
	 */
	public function testValidatePhoneNeedsTenDigits(): void
	{
		$this->assertTrue(Validate::phone('555.123.4567'));
		$this->assertFalse(Validate::phone('555-1234'));
		$this->assertFalse(Validate::phone('   '));
		$this->assertFalse(Validate::phone(null));
	}

	/**
	 * @return void
	 */
	public function testValidateBool(): void
	{
		$this->assertTrue(Validate::bool('true'));
		$this->assertTrue(Validate::bool('0'));
		$this->assertTrue(Validate::bool(false));
		$this->assertFalse(Validate::bool('maybe'));
	}

	/**
	 * @return void
	 */
	public function testValidateNetworkTypes(): void
	{
		$this->assertTrue(Validate::ip('::1'));
		$this->assertFalse(Validate::ip('1.2.3'));
		$this->assertTrue(Validate::mac('00-1A-2B-3C-4D-5E'));
		$this->assertFalse(Validate::mac('001A2B'));
		$this->assertTrue(Validate::url('http://localhost:8080/x?y=1'));
		$this->assertFalse(Validate::url('example.com'));
		$this->assertTrue(Validate::email('a.b+c@example.co'));
		$this->assertFalse(Validate::email('a@'));
	}

	/**
	 * @return void
	 */
	public function testValidateImageRejectsNonUploads(): void
	{
		$this->assertFalse(Validate::image('x'));
		$this->assertFalse(Validate::image(['name' => 'a.png']));
		$this->assertFalse(Validate::image([
			'name' => 'a.png',
			'type' => 'image/png',
			'size' => 1,
			'tmp_name' => __FILE__,
		]), 'A path that was not uploaded via HTTP must be rejected.');
	}

	/**
	 * @return void
	 */
	public function testSanitizeIntReturnsNullWhenNoDigits(): void
	{
		$this->assertSame(12, Sanitize::int('12abc'));
		$this->assertSame(-5, Sanitize::int('-5'));
		$this->assertNull(Sanitize::int('abc'));
		$this->assertNull(Sanitize::int('-'));
		$this->assertNull(Sanitize::int(null));
	}

	/**
	 * @return void
	 */
	public function testSanitizeFloat(): void
	{
		$this->assertSame(3.5, Sanitize::float('$3.5'));
		$this->assertNull(Sanitize::float('abc'));
		$this->assertNull(Sanitize::float(null));
	}

	/**
	 * @return void
	 */
	public function testSanitizeStringStripsTagsAndTrims(): void
	{
		$this->assertSame('hi there', Sanitize::string(" <em>hi</em> <br/>there \n"));
		// strip_tags drops markup only; inner text is kept.
		$this->assertSame('alert(1)', Sanitize::string('<script>alert(1)</script>'));
		$this->assertNull(Sanitize::string(null));
	}

	/**
	 * @return void
	 */
	public function testSanitizePhoneIpMacReturnNullWhenInvalid(): void
	{
		$this->assertSame('5551234567', Sanitize::phone('+(555) 123-4567'));
		$this->assertNull(Sanitize::phone('12345'));
		$this->assertSame('10.0.0.1', Sanitize::ip('10.0.0.1'));
		$this->assertNull(Sanitize::ip('nope'));
		$this->assertNull(Sanitize::mac('nope'));
	}

	/**
	 * @return void
	 */
	public function testSanitizeEmailAndUrlRemoveIllegalCharacters(): void
	{
		$this->assertSame('ada@example.com', Sanitize::email('ada @example.com'));
		$this->assertSame('https://example.com/a', Sanitize::url("https://example.com/a\n"));
		$this->assertSame(Sanitize::url('example.com'), Sanitize::domain('example.com'));
	}

	/**
	 * @return void
	 */
	public function testSanitizeBool(): void
	{
		$this->assertTrue(Sanitize::bool('on'));
		$this->assertFalse(Sanitize::bool('no'));
		$this->assertNull(Sanitize::bool('perhaps'));
	}
}
