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
	 * fetchWhereWithoutJoins must use the hook-free storage fetch, not getRows or a missing fetchWhere.
	 *
	 * @return void
	 */
	public function testFetchWhereWithoutJoinsCallsGetRows(): void
	{
		$rows = SpyFetchModel::fetchWhereWithoutJoins(['userId' => 1]);
		$this->assertSame([], $rows);
		$this->assertContains('fetchWithoutHooks', SpyFetchStorage::$calls);
		$this->assertNotContains('fetchWhere', SpyFetchStorage::$calls);
		$this->assertNotContains('getRows', SpyFetchStorage::$calls, 'getRows runs app order/where hooks that may reference joined aliases.');
	}

	/**
	 * BatchMap::exists uses fetchWhereWithoutJoins and therefore the hook-free fetch.
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

		$this->assertContains('fetchWithoutHooks', SpyFetchStorage::$calls);
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

		$this->assertContains('fetchWithoutHooks', SpyFetchStorage::$calls);
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
	 * Storage::fetchWithoutHooks() must not run setOrderBy() /
	 * setGroupBy(), which apps write against joined aliases (e.g. ORDER
	 * BY c.last_message_at), but must keep setCustomWhere() scopes.
	 *
	 * @return void
	 */
	public function testFetchWithoutHooksSkipsOrderButKeepsCustomWhere(): void
	{
		$adapter = $this->getMockBuilder(\Proto\Database\Adapters\Adapter::class)
			->disableOriginalConstructor()
			->getMock();
		$adapter->method('fetch')->willReturnCallback(function (string $sql): array
		{
			HookedStorage::$sql = $sql;
			return [(object)['id' => 1]];
		});
		HookedStorage::$adapter = $adapter;

		$storage = new HookedStorage(new HookedModel());
		$rows = $storage->fetchWithoutHooks(['userId' => 3]);

		$this->assertCount(1, $rows);
		$this->assertStringNotContainsString('last_message_at', HookedStorage::$sql);
		$this->assertStringContainsString('user_id', HookedStorage::$sql);
		$this->assertStringContainsString('h.tenant_id = 5', HookedStorage::$sql, 'Tenant/visibility scopes in setCustomWhere must still apply.');
	}

	/**
	 * findAll() callbacks that bind params by reference keep them.
	 *
	 * @return void
	 */
	public function testFindAllCallbackParamsByReferenceAreBound(): void
	{
		$captured = [];
		$adapter = $this->getMockBuilder(\Proto\Database\Adapters\Adapter::class)
			->disableOriginalConstructor()
			->getMock();
		$adapter->method('fetch')->willReturnCallback(function (string $sql, array $params) use (&$captured): array
		{
			$captured = $params;
			return [];
		});
		HookedStorage::$adapter = $adapter;

		$storage = new HookedStorage(new HookedModel());
		$storage->findAll(function (object $sql, array &$params): void
		{
			$sql->where('h.user_id = ?');
			$params[] = 42;
		});

		$this->assertSame([42], $captured);
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
	public function fetchWithoutHooks(mixed $filter = null): array
	{
		self::$calls[] = 'fetchWithoutHooks';
		return self::$rows;
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

/**
 * Storage whose hooks reference a joined alias, like Rally's
 * ConversationParticipantStorage.
 *
 * @package Proto\Tests\Unit\Models
 */
final class HookedStorage extends Storage
{
	/**
	 * @var \Proto\Database\Adapters\Adapter|null
	 */
	public static ?\Proto\Database\Adapters\Adapter $adapter = null;

	/**
	 * @var string
	 */
	public static string $sql = '';

	/**
	 * @param mixed ...$fields
	 * @return object
	 */
	public function select(...$fields): object
	{
		return new \Proto\Database\QueryBuilder\AdapterProxy(
			(new \Proto\Database\QueryBuilder\Select('hooked', 'h'))->select('h.*'),
			self::$adapter
		);
	}

	/**
	 * @param object $sql
	 * @param array|null $modifiers
	 * @param array|null $params
	 * @return void
	 */
	protected function setOrderBy(object $sql, ?array $modifiers = null, ?array &$params = null): void
	{
		$sql->orderBy('COALESCE(c.last_message_at, c.created_at) DESC');
	}

	/**
	 * @param object $sql
	 * @param array|null $modifiers
	 * @param array|null $params
	 * @return void
	 */
	protected function setCustomWhere(object $sql, ?array $modifiers = null, ?array &$params = null): void
	{
		// Own-table scope (always) plus a join-dependent one (only with modifiers)
		$sql->where('h.tenant_id = 5');
		if (!empty($modifiers['search']))
		{
			$sql->where('c.last_message_at IS NOT NULL');
		}
	}
}

/**
 * @package Proto\Tests\Unit\Models
 */
final class HookedModel extends Model
{
	/**
	 * @var string|null
	 */
	protected static ?string $tableName = 'hooked';

	/**
	 * @var string|null
	 */
	protected static ?string $alias = 'h';

	/**
	 * @var array
	 */
	protected static array $fields = ['id', 'userId'];

	/**
	 * @var string
	 */
	protected static string $storageType = HookedStorage::class;
}
