<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Database\Adapters;

use Proto\Database\Adapters\Sql\Mysql\MysqliQueryHelper;
use Proto\Tests\Test;

/**
 * MysqliQueryHelperTest
 *
 * Covers SHOW/DESCRIBE placeholder inlining used when MariaDB rejects
 * bound `?` on those statements.
 *
 * @package Proto\Tests\Unit\Database\Adapters
 */
final class MysqliQueryHelperTest extends Test
{
	/**
	 * Pure helper tests; no database required.
	 *
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @var MysqliQueryHelper
	 */
	private MysqliQueryHelper $helper;

	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->helper = new MysqliQueryHelper();
	}

	/**
	 * @return void
	 */
	public function testDetectsShowAndDescribeAsNonPreparable(): void
	{
		$this->assertTrue($this->helper->isNonPreparableStatement('SHOW TABLES LIKE ?'));
		$this->assertTrue($this->helper->isNonPreparableStatement('  show columns from `t` like ?'));
		$this->assertTrue($this->helper->isNonPreparableStatement('DESCRIBE users'));
		$this->assertTrue($this->helper->isNonPreparableStatement('DESC users'));
		$this->assertFalse($this->helper->isNonPreparableStatement('SELECT * FROM users WHERE id = ?'));
		$this->assertFalse($this->helper->isNonPreparableStatement('EXPLAIN SELECT 1'));
	}

	/**
	 * @return void
	 */
	public function testInlinesShowTablesLikePlaceholder(): void
	{
		[$sql, $params] = $this->helper->resolveNonPreparableSql(
			'SHOW TABLES LIKE ?',
			['post_media']
		);

		$this->assertSame("SHOW TABLES LIKE 'post_media'", $sql);
		$this->assertSame([], $params);
	}

	/**
	 * @return void
	 */
	public function testInlinesShowColumnsLikePlaceholder(): void
	{
		[$sql, $params] = $this->helper->resolveNonPreparableSql(
			"SHOW COLUMNS FROM `post_media` LIKE ?",
			['path']
		);

		$this->assertSame("SHOW COLUMNS FROM `post_media` LIKE 'path'", $sql);
		$this->assertSame([], $params);
	}

	/**
	 * @return void
	 */
	public function testEscapesQuotesInsideInlinedLiterals(): void
	{
		[$sql, $params] = $this->helper->resolveNonPreparableSql(
			'SHOW TABLES LIKE ?',
			["O'Brien"]
		);

		$this->assertSame("SHOW TABLES LIKE 'O\\'Brien'", $sql);
		$this->assertSame([], $params);
	}

	/**
	 * @return void
	 */
	public function testLeavesPreparableSqlUnchanged(): void
	{
		[$sql, $params] = $this->helper->resolveNonPreparableSql(
			'SELECT id FROM users WHERE email = ?',
			['a@b.com']
		);

		$this->assertSame('SELECT id FROM users WHERE email = ?', $sql);
		$this->assertSame(['a@b.com'], $params);
	}

	/**
	 * Empty/short params must not rewrite `?` away; prepare should still
	 * fail visibly rather than running half-baked SQL.
	 *
	 * @return void
	 */
	public function testDoesNotRewriteWhenParamsMissing(): void
	{
		[$sql, $params] = $this->helper->resolveNonPreparableSql(
			'SHOW TABLES LIKE ?',
			[]
		);

		$this->assertSame('SHOW TABLES LIKE ?', $sql);
		$this->assertSame([], $params);
	}

	/**
	 * @return void
	 */
	public function testShowWithoutPlaceholdersClearsParams(): void
	{
		[$sql, $params] = $this->helper->resolveNonPreparableSql(
			"SHOW TABLES LIKE 'users'",
			['ignored']
		);

		$this->assertSame("SHOW TABLES LIKE 'users'", $sql);
		$this->assertSame([], $params);
	}
}
