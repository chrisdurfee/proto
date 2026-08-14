<?php declare(strict_types=1);
namespace Proto\Tests\Unit\Database\QueryBuilder;

use Proto\Database\QueryBuilder\Create;
use PHPUnit\Framework\TestCase;

/**
 * CreateCallTest
 *
 * @package Proto\Tests\Unit\Database\QueryBuilder
 */
class CreateCallTest extends TestCase
{
	/**
	 * @return void
	 */
	public function testUnknownColumnTypeThrows(): void
	{
		$this->expectException(\BadMethodCallException::class);
		$create = new Create('example_table');
		$create->raw('coordinates POINT NULL');
	}

	/**
	 * @return void
	 */
	public function testKnownColumnTypeWorks(): void
	{
		$create = new Create('example_table');
		$field = $create->varchar('name', 100);
		$this->assertNotNull($field);
		$sql = $create->render();
		$this->assertStringContainsString('name', $sql);
	}
}
