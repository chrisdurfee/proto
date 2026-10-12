<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Api;

use Proto\Api\Validator;
use Proto\Tests\Test;

/**
 * ValidatorTest
 *
 * Rule parsing, sanitization write-back, error reporting and the
 * allow-list that keeps rule names from reaching arbitrary methods.
 *
 * @package Proto\Tests\Unit\Api
 */
final class ValidatorTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @param array $data
	 * @param array $rules
	 * @return Validator
	 */
	private function validate(array &$data, array $rules): Validator
	{
		return Validator::create($data, $rules);
	}

	/**
	 * @return void
	 */
	public function testEmptyRulesAreValid(): void
	{
		$data = ['a' => 'x'];
		$validator = $this->validate($data, []);

		$this->assertTrue($validator->isValid());
		$this->assertSame([], $validator->getErrors());
		$this->assertSame('', $validator->getMessage());
	}

	/**
	 * @return void
	 */
	public function testMissingRequiredFieldIsReported(): void
	{
		$data = [];
		$validator = $this->validate($data, ['name' => 'string:100|required']);

		$this->assertFalse($validator->isValid());
		$this->assertSame(['The key name is not set.'], $validator->getErrors());
		$this->assertSame(['name' => ['This field is required.']], $validator->getFieldErrors());
	}

	/**
	 * @return void
	 */
	public function testEmptyStringCountsAsMissingForRequired(): void
	{
		$data = ['name' => ''];
		$validator = $this->validate($data, ['name' => 'string|required']);

		$this->assertFalse($validator->isValid());
	}

	/**
	 * @return void
	 */
	public function testOptionalMissingFieldIsSkipped(): void
	{
		$data = ['name' => null];
		$validator = $this->validate($data, ['name' => 'email']);

		$this->assertTrue($validator->isValid());
		$this->assertNull($data['name']);
	}

	/**
	 * Zero is a value, not an absent field.
	 *
	 * @return void
	 */
	public function testZeroSatisfiesRequired(): void
	{
		$data = ['count' => '0'];
		$validator = $this->validate($data, ['count' => 'int|required']);

		$this->assertTrue($validator->isValid());
		$this->assertSame(0, $data['count']);
	}

	/**
	 * @return void
	 */
	public function testStringIsSanitizedAndWrittenBack(): void
	{
		$data = ['name' => '  <b>Ada</b>  '];
		$validator = $this->validate($data, ['name' => 'string:20']);

		$this->assertTrue($validator->isValid());
		$this->assertSame('Ada', $data['name']);
	}

	/**
	 * @return void
	 */
	public function testStringOverMaxLengthIsRejected(): void
	{
		$data = ['name' => str_repeat('a', 11)];
		$validator = $this->validate($data, ['name' => 'string:10']);

		$this->assertFalse($validator->isValid());
		$this->assertSame(['The value name is over the max size.'], $validator->getErrors());
		$this->assertSame(['name' => ['Must be 10 characters or fewer.']], $validator->getFieldErrors());
	}

	/**
	 * @return void
	 */
	public function testStringAtMaxLengthIsAccepted(): void
	{
		$data = ['name' => str_repeat('a', 10)];
		$this->assertTrue($this->validate($data, ['name' => 'string:10'])->isValid());
	}

	/**
	 * @return void
	 */
	public function testObjectDataIsValidatedAndWrittenBack(): void
	{
		$data = (object)['age' => ' 42 '];
		$validator = Validator::create($data, ['age' => 'int']);

		$this->assertTrue($validator->isValid());
		$this->assertSame(42, $data->age);
	}

	/**
	 * @return void
	 */
	public function testIntWithLengthLimitDoesNotThrow(): void
	{
		$data = ['id' => '12345'];
		$validator = $this->validate($data, ['id' => 'int:11|required']);

		$this->assertTrue($validator->isValid());
		$this->assertSame(12345, $data['id']);
	}

	/**
	 * @return void
	 */
	public function testIntLengthLimitIsEnforced(): void
	{
		$data = ['id' => '123456'];
		$this->assertFalse($this->validate($data, ['id' => 'int:5'])->isValid());
	}

	/**
	 * Non-numeric input used to be coerced to 0 and accepted.
	 *
	 * @return void
	 */
	public function testNonNumericIntIsRejected(): void
	{
		$data = ['id' => 'abc'];
		$validator = $this->validate($data, ['id' => 'int']);

		$this->assertFalse($validator->isValid());
		$this->assertSame(['id' => ['The value is not valid.']], $validator->getFieldErrors());
	}

	/**
	 * @return void
	 */
	public function testNativeIntFromJsonIsAccepted(): void
	{
		$data = ['id' => 7];
		$this->assertTrue($this->validate($data, ['id' => 'int:3'])->isValid());
		$this->assertSame(7, $data['id']);
	}

	/**
	 * JSON numbers for string rules used to throw a TypeError.
	 *
	 * @return void
	 */
	public function testNativeIntForStringRuleIsCast(): void
	{
		$data = ['zip' => 12345];
		$validator = $this->validate($data, ['zip' => 'string:10']);

		$this->assertTrue($validator->isValid());
		$this->assertSame('12345', $data['zip']);
	}

	/**
	 * @return void
	 */
	public function testArrayValueIsRejectedNotThrown(): void
	{
		$data = ['name' => ['x', 'y']];
		$validator = $this->validate($data, ['name' => 'string']);

		$this->assertFalse($validator->isValid());
		$this->assertArrayHasKey('name', $validator->getFieldErrors());
	}

	/**
	 * @return void
	 */
	public function testFloatWithLimit(): void
	{
		$data = ['price' => '19.99'];
		$validator = $this->validate($data, ['price' => 'float:10']);

		$this->assertTrue($validator->isValid());
		$this->assertSame(19.99, $data['price']);
	}

	/**
	 * @return void
	 */
	public function testValidEmailIsAccepted(): void
	{
		$data = ['email' => 'ada@example.com'];
		$this->assertTrue($this->validate($data, ['email' => 'email:255|required'])->isValid());
	}

	/**
	 * @return void
	 */
	public function testInvalidEmailIsRejected(): void
	{
		$data = ['email' => 'not an email'];
		$validator = $this->validate($data, ['email' => 'email']);

		$this->assertFalse($validator->isValid());
		$this->assertSame(['The value email is not correct.'], $validator->getErrors());
	}

	/**
	 * @return void
	 */
	public function testUrlRule(): void
	{
		$good = ['site' => 'https://example.com/path'];
		$bad = ['site' => 'javascript alert'];

		$this->assertTrue($this->validate($good, ['site' => 'url'])->isValid());
		$this->assertFalse($this->validate($bad, ['site' => 'url'])->isValid());
	}

	/**
	 * @return void
	 */
	public function testBoolRuleNormalisesTruthyStrings(): void
	{
		$data = ['active' => 'yes', 'hidden' => 'off'];
		$validator = $this->validate($data, ['active' => 'bool', 'hidden' => 'bool']);

		$this->assertTrue($validator->isValid());
		$this->assertTrue($data['active']);
		$this->assertFalse($data['hidden']);
	}

	/**
	 * "maybe" used to sanitize to null and pass.
	 *
	 * @return void
	 */
	public function testBoolRuleRejectsNonBoolean(): void
	{
		$data = ['active' => 'maybe'];
		$this->assertFalse($this->validate($data, ['active' => 'bool'])->isValid());
	}

	/**
	 * @return void
	 */
	public function testNativeBoolWithLimitDoesNotThrow(): void
	{
		$data = ['active' => true];
		$this->assertTrue($this->validate($data, ['active' => 'bool:1'])->isValid());
	}

	/**
	 * @return void
	 */
	public function testIpMacPhoneDomainRules(): void
	{
		$good = ['ip' => '10.0.0.1', 'mac' => '00:1A:2B:3C:4D:5E', 'phone' => '(555) 123-4567', 'host' => 'example.com'];
		$validator = $this->validate($good, ['ip' => 'ip', 'mac' => 'mac', 'phone' => 'phone', 'host' => 'domain']);
		$this->assertTrue($validator->isValid(), $validator->getMessage());
		$this->assertSame('5551234567', $good['phone']);

		$bad = ['ip' => '999.1.1.1', 'mac' => 'zz', 'phone' => '123'];
		$validator = $this->validate($bad, ['ip' => 'ip', 'mac' => 'mac', 'phone' => 'phone']);
		$this->assertFalse($validator->isValid());
		$this->assertSame(['ip', 'mac', 'phone'], array_keys($validator->getFieldErrors()));
	}

	/**
	 * Rule names map to static methods; an unknown name must never be
	 * dispatched.
	 *
	 * @return void
	 */
	public function testUnknownRuleIsRejectedWithoutDispatch(): void
	{
		$data = ['cmd' => 'whoami'];
		$validator = $this->validate($data, ['cmd' => 'filter']);

		$this->assertFalse($validator->isValid());
		$this->assertSame([
			"Invalid sanitizer type 'filter' for key cmd.",
			"Invalid validation type 'filter' for key cmd.",
		], $validator->getErrors());
		$this->assertSame('whoami', $data['cmd']);
	}

	/**
	 * @return void
	 */
	public function testRuleNamesAreCaseSensitive(): void
	{
		$data = ['a' => 'x'];
		$this->assertFalse($this->validate($data, ['a' => 'STRING'])->isValid());
	}

	/**
	 * @return void
	 */
	public function testMessageJoinsAllErrorsAcrossFields(): void
	{
		$data = ['email' => 'bad'];
		$validator = $this->validate($data, ['email' => 'email', 'name' => 'string|required']);

		$this->assertSame(
			'The value email is not correct., The key name is not set.',
			$validator->getMessage()
		);
		$this->assertCount(2, $validator->getFieldErrors());
	}

	/**
	 * @return void
	 */
	public function testValidFieldsDoNotAppearInFieldErrors(): void
	{
		$data = ['a' => 'ok', 'b' => 'not-an-email'];
		$validator = $this->validate($data, ['a' => 'string', 'b' => 'email']);

		$this->assertSame(['b'], array_keys($validator->getFieldErrors()));
	}

	/**
	 * @return void
	 */
	public function testImageRuleRejectsNonUpload(): void
	{
		$data = ['avatar' => 'not-a-file'];
		$validator = $this->validate($data, ['avatar' => 'image:100|required']);

		$this->assertFalse($validator->isValid());
		$this->assertSame(['avatar' => ['Not a valid image file.']], $validator->getFieldErrors());
		$this->assertSame('not-a-file', $data['avatar']);
	}
}
