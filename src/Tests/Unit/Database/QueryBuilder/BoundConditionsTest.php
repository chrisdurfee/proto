<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Database\QueryBuilder;

use PHPUnit\Framework\TestCase;
use Proto\Database\QueryBuilder\AdapterProxy;
use Proto\Database\QueryBuilder\Select;

/**
 * BoundConditionsTest
 *
 * whereBetween() and whereJson() used to splice values straight into
 * the SQL string. Values must now be bound (table builder) or rejected
 * (raw query builder).
 *
 * @package Proto\Tests\Unit\Database\QueryBuilder
 */
final class BoundConditionsTest extends TestCase
{
	/**
	 * @return void
	 */
	public function testRawWhereBetweenRejectsStringValues(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->select()->whereBetween('a.created_at', "x' OR 1=1 -- ", '2026-01-01');
	}

	/**
	 * @return void
	 */
	public function testRawWhereBetweenAllowsPlaceholdersAndNumbers(): void
	{
		$sql = (string)$this->select()->whereBetween('a.score', '?', 10);
		$this->assertStringContainsString('a.score BETWEEN ? AND 10', $sql);
	}

	/**
	 * @return void
	 */
	public function testRawWhereJsonRejectsInlineValues(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->select()->whereJson('a.tags', ['id' => "1'"]);
	}

	/**
	 * @return void
	 */
	public function testWhereJsonRejectsInjectedPath(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->select()->whereJson('a.tags', '?', "$') OR 1=1 -- ");
	}

	/**
	 * @return void
	 */
	public function testProxyWhereBetweenBindsValues(): void
	{
		$proxy = new AdapterProxy($this->select());
		$proxy->whereBetween('a.created_at', "x' OR 1=1 -- ", '2026-01-01');

		$this->assertStringContainsString('a.created_at BETWEEN ? AND ?', (string)$proxy);
		$this->assertSame(["x' OR 1=1 -- ", '2026-01-01'], $proxy->getParams());
	}

	/**
	 * @return void
	 */
	public function testProxyWhereJsonBindsValue(): void
	{
		$proxy = new AdapterProxy($this->select());
		$proxy->whereJson('a.tags', ['id' => "1'"], '$.list');

		$this->assertStringContainsString("JSON_CONTAINS(a.tags, ?, '$.list')", (string)$proxy);
		$this->assertSame([json_encode(['id' => "1'"])], $proxy->getParams());
	}

	/**
	 * @return Select
	 */
	private function select(): Select
	{
		return new Select('items', 'a');
	}
}
