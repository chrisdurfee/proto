<?php declare(strict_types=1);
namespace Tests\Unit\Proto\Utils;

use SebastianBergmann\Type\VoidType;
use Tests\Test;
use Proto\Utils\Strings;

/**
 * StringsTest
 *
 * This will test the Strings utility class.
 *
 * @package Tests\Unit\Proto\Utils
 */
final class StringsTest extends Test
{
    /**
     * This is the strings utility class name.
     *
     * @var string $className
     */
    protected string $className = Strings::class;

    /**
     * This will test the abbreviate state method.
     *
     * @return void
     */
    public function testAbbreviateState(): void
    {
        $result = $this->className::abbreviateState("Utah");
        $this->assertEquals("UT", $result);

        $result = $this->className::abbreviateState("utah");
        $this->assertEquals("UT", $result);

        $result = $this->className::abbreviateState("CALIFORNIA");
        $this->assertEquals("CA", $result);

        $result = $this->className::abbreviateState("New York");
        $this->assertEquals("NY", $result);

        $result = $this->className::abbreviateState("arizona");
        $this->assertEquals("AZ", $result);
    }

    /**
     * This will test the abbreviate state method with
     * a non U.S. state.
     *
     * @return void
     */
    public function testAbbreviateStateFail(): void
    {
        $result = $this->className::abbreviateState("Ontario");
        $this->assertFalse($result);

        $result = $this->className::abbreviateState("ON");
        $this->assertFalse($result);

        $result = $this->className::abbreviateState("alberta");
        $this->assertFalse($result);

        $result = $this->className::abbreviateState("ab");
        $this->assertFalse($result);
    }

    /**
     * This will test the abbreviateCanadianProvince method.
     *
     * @return void
     */
    public function testAbbreviateCanadianProvince(): void
    {
        $result = $this->className::abbreviateCanadianProvince("alberta");
        $this->assertEquals("AB", $result);

        $result = $this->className::abbreviateCanadianProvince("bc");
        $this->assertEquals("BC", $result);

        $result = $this->className::abbreviateCanadianProvince("British Columbia");
        $this->assertEquals("BC", $result);

        $result = $this->className::abbreviateCanadianProvince("Quebec");
        $this->assertEquals("QC", $result);

        $result = $this->className::abbreviateCanadianProvince("QC");
        $this->assertEquals("QC", $result);
    }

    /**
     * This will test the abbreviate state method with
     * a non Canadian province.
     *
     * @return void
     */
    public function testAbbreviateCanadianProvinceFail(): void
    {
        $result = $this->className::abbreviateCanadianProvince("Utah");
        $this->assertFalse($result);

        $result = $this->className::abbreviateCanadianProvince("oh");
        $this->assertFalse($result);

        $result = $this->className::abbreviateCanadianProvince("Ohio");
        $this->assertFalse($result);

        $result = $this->className::abbreviateCanadianProvince("north carolina");
        $this->assertFalse($result);
    }

    /**
     * This will test the format phone method.
     *
     * @return void
     */
    public function testFormatPhone(): void
    {
        $result = $this->className::formatPhone("234567890");
        $this->assertEquals("+1234567890", $result);

        $result = $this->className::formatPhone("+1234567890");
        $this->assertEquals("+1234567890", $result);

        $result = $this->className::formatPhone("(123) 456-7890");
        $this->assertEquals("+1234567890", $result);
    }

    /**
     * This will test the formatEin method.
     *
     * @return void
     */
	public function testFormatEin(): void
    {
        $this->assertEquals(
            '12-3456789',
            Strings::formatEin('123456789')
        );
    }

    /**
     * This will test the cleanPhone method.
     *
     * @return void
     */
	public function testCleanPhone(): void
    {
        $this->assertEquals(
            '234567890',
            Strings::cleanPhone('(123) 456-7890')
        );
        $this->assertEquals(
            '',
            Strings::cleanPhone('')
        );
    }

    /**
     * This will test the snake case method.
     *
     * @return void
     */
    public function testSnakeCase(): void
    {
        $this->assertEquals(
            'test_snake_case',
            Strings::snakeCase('testSnakeCase')
        );
    }

    /**
     * This will test the kebabCase method.
     *
     * @return void
     */
    public function testKebabCase(): void
    {
        $this->assertEquals(
            'test-kebab-case',
            Strings::kebabCase('testKebabCase')
        );
    }

    /**
     * This will test the camelCase method.
     *
     * @return void
     */
    public function testCamelCase(): void
    {
        $this->assertEquals(
            'testCamelCase',
            Strings::camelCase('test_camel_case')
        );
    }

    /**
     * This will test the hyphen method.
     *
     * @return void
     */
    public function testHyphen(): void
    {
        $this->assertEquals(
            'test-hyphen',
            Strings::hyphen('test_hyphen')
        );
    }
}