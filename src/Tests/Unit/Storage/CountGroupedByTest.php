<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Storage;

use Proto\Database\QueryBuilder\QueryHandler;
use Proto\Models\Model;
use Proto\Tests\Test;

/**
 * CountGroupedByTest
 *
 * @package Proto\Tests\Unit\Storage
 */
final class CountGroupedByTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * String select fields are alias-prefixed. Grouped counts must use the
	 * raw-array form or SQL becomes `p.COUNT(*)`.
	 *
	 * @return void
	 */
	public function testStringSelectPrefixesAliasOntoAggregates(): void
	{
		$sql = (string)QueryHandler::table('posts', 'p')
			->select('p.user_id AS grouped_key', 'COUNT(*) AS grouped_count');

		$this->assertStringContainsString('p.p.user_id AS grouped_key', $sql);
		$this->assertStringContainsString('p.COUNT(*) AS grouped_count', $sql);
	}

	/**
	 * The form Storage::countGroupedBy() uses must stay raw.
	 *
	 * @return void
	 */
	public function testRawArraySelectKeepsQualifiedGroupColumns(): void
	{
		$sql = (string)QueryHandler::table('posts', 'p')
			->select(
				[['p.user_id'], 'grouped_key'],
				[['COUNT(*)'], 'grouped_count']
			);

		$this->assertStringContainsString('p.user_id AS grouped_key', $sql);
		$this->assertStringContainsString('COUNT(*) AS grouped_count', $sql);
		$this->assertStringNotContainsString('p.p.user_id', $sql);
		$this->assertStringNotContainsString('p.COUNT(*)', $sql);
		$this->assertStringContainsString('FROM posts AS p', $sql);
	}

	/**
	 * @return void
	 */
	public function testCountGroupedByEmptyIdsShortCircuits(): void
	{
		$model = new class extends Model
		{
			protected static ?string $tableName = 'count_grouped_empty';
			protected static array $fields = ['id', 'postId'];
		};

		$this->assertSame([], $model::countGroupedBy('postId', []));
	}
}
