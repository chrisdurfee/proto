<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Models;

use Proto\Models\Model;
use Proto\Storage\Storage;
use Proto\Support\BatchMap;
use Proto\Tests\Test;

/**
 * FetchWhereWithoutJoinsTest
 *
 * @package Proto\Tests\Unit\Models
 */
final class FetchWhereWithoutJoinsTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		SpyFetchStorage::$calls = [];
		SpyFetchStorage::$rows = [];
	}

	/**
	 * fetchWhereWithoutJoins must call getRows on storage, not a missing fetchWhere.
	 *
	 * @return void
	 */
	public function testFetchWhereWithoutJoinsCallsGetRows(): void
	{
		$rows = SpyFetchModel::fetchWhereWithoutJoins(['userId' => 1]);
		$this->assertSame([], $rows);
		$this->assertContains('getRows', SpyFetchStorage::$calls);
		$this->assertNotContains('fetchWhere', SpyFetchStorage::$calls);
	}

	/**
	 * BatchMap::exists uses fetchWhereWithoutJoins and therefore hits getRows.
	 *
	 * @return void
	 */
	public function testBatchMapExistsUsesGetRows(): void
	{
		SpyFetchStorage::$rows = [
			(object)['id' => 10, 'post_id' => 1, 'user_id' => 9]
		];

		$rows = [(object)['id' => 1], (object)['id' => 2]];
		BatchMap::exists($rows, SpyFetchModel::class, 'postId', 'liked');

		$this->assertContains('getRows', SpyFetchStorage::$calls);
		$this->assertTrue($rows[0]->liked);
		$this->assertFalse($rows[1]->liked);
	}

	/**
	 * BatchMap::field uses the same join-free fetch path.
	 *
	 * @return void
	 */
	public function testBatchMapFieldUsesGetRows(): void
	{
		SpyFetchStorage::$rows = [
			(object)['id' => 10, 'post_id' => 1, 'name' => 'Ada']
		];

		$rows = [(object)['id' => 1]];
		BatchMap::field($rows, SpyFetchModel::class, 'postId', 'name', 'authorName', null);

		$this->assertContains('getRows', SpyFetchStorage::$calls);
		$this->assertSame('Ada', $rows[0]->authorName);
	}

	/**
	 * fetchWhereWithoutJoins camelCases storage columns so BatchMap
	 * can read `groupId` / `postId` without TypeError.
	 *
	 * @return void
	 */
	public function testFetchWhereWithoutJoinsConvertsSnakeCaseKeys(): void
	{
		SpyFetchStorage::$rows = [
			(object)['id' => 10, 'post_id' => 9, 'user_id' => 3, 'name' => 'Ada']
		];

		$rows = SpyFetchModel::fetchWhereWithoutJoins(['userId' => 3]);
		$this->assertCount(1, $rows);
		$this->assertSame(9, $rows[0]->postId);
		$this->assertSame(3, $rows[0]->userId);
		$this->assertSame('Ada', $rows[0]->name);
	}

	/**
	 * Missing FK properties are skipped instead of TypeErroring.
	 *
	 * @return void
	 */
	public function testBatchMapExistsSkipsMissingForeignKey(): void
	{
		SpyFetchStorage::$rows = [
			(object)['id' => 10, 'user_id' => 9]
		];

		$rows = [(object)['id' => 1]];
		BatchMap::exists($rows, SpyFetchModel::class, 'groupId', 'isFavorited');

		$this->assertFalse($rows[0]->isFavorited);
	}
}

/**
 * @package Proto\Tests\Unit\Models
 */
final class SpyFetchStorage extends Storage
{
	/**
	 * @var array<int, string>
	 */
	public static array $calls = [];

	/**
	 * @var array<int, object>
	 */
	public static array $rows = [];

	/**
	 * @param mixed $filter
	 * @param int|null $offset
	 * @param int|null $limit
	 * @param array|null $modifiers
	 * @return object
	 */
	public function getRows(mixed $filter = null, ?int $offset = null, ?int $limit = null, ?array $modifiers = null): object
	{
		self::$calls[] = 'getRows';
		return (object)['rows' => self::$rows];
	}

	/**
	 * @param mixed $filter
	 * @return array
	 */
	public function fetchWhere(mixed $filter): array
	{
		self::$calls[] = 'fetchWhere';
		return [];
	}
}

/**
 * @package Proto\Tests\Unit\Models
 */
final class SpyFetchModel extends Model
{
	/**
	 * @var string|null
	 */
	protected static ?string $tableName = 'spy_fetch';

	/**
	 * @var array
	 */
	protected static array $fields = ['id', 'postId', 'userId', 'name'];

	/**
	 * @var string
	 */
	protected static string $storageType = SpyFetchStorage::class;
}
