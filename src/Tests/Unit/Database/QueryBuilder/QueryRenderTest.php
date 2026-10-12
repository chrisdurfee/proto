<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Database\QueryBuilder;

use Proto\Database\QueryBuilder\AdapterProxy;
use Proto\Database\QueryBuilder\Delete;
use Proto\Database\QueryBuilder\Insert;
use Proto\Database\QueryBuilder\Replace;
use Proto\Database\QueryBuilder\Select;
use Proto\Database\QueryBuilder\Update;
use Proto\Tests\Test;

/**
 * QueryRenderTest
 *
 * SQL rendering for Select/Insert/Update/Delete/Replace, and the
 * AdapterProxy layer that turns user values into bound parameters.
 * Whitespace is normalised so assertions track structure, not padding.
 *
 * @package Proto\Tests\Unit\Database\QueryBuilder
 */
final class QueryRenderTest extends Test
{
	/**
	 * @var bool
	 */
	protected bool $useTransactions = false;

	/**
	 * Hostile value used to prove user input never lands in SQL text.
	 */
	private const EVIL = "x' OR '1'='1";

	/**
	 * @param object|string $sql
	 * @return string
	 */
	private function sql(object|string $sql): string
	{
		return trim((string)preg_replace('/\s+/', ' ', (string)$sql));
	}

	/**
	 * @return void
	 */
	public function testSelectDefaultsAliasToTableName(): void
	{
		$sql = (new Select('users'))->select('id', 'name');
		$this->assertSame('SELECT users.id, users.name FROM users', $this->sql($sql));
	}

	/**
	 * @return void
	 */
	public function testSelectWithAliasAndStar(): void
	{
		$sql = (new Select('users', 'u'))->select();
		$this->assertSame('SELECT u.* FROM users AS u', $this->sql($sql));
	}

	/**
	 * @return void
	 */
	public function testSelectAliasedAndRawFields(): void
	{
		$sql = (new Select('users', 'u'))->select(
			['first_name', 'firstName'],
			[['COUNT(*)'], 'total'],
			['NOW()']
		);
		$this->assertSame(
			'SELECT u.first_name AS firstName, COUNT(*) AS total, NOW() FROM users AS u',
			$this->sql($sql)
		);
	}

	/**
	 * @return void
	 */
	public function testSelectWithJoinAddsJoinFields(): void
	{
		$sql = (new Select('users', 'u'))
			->select('id')
			->join([
				'table' => 'roles',
				'alias' => 'r',
				'on' => ['u.role_id = r.id', ['u.org_id', 'r.org_id']],
				'fields' => ['name', ['slug', 'roleSlug']],
			]);

		$this->assertSame(
			'SELECT u.id, r.name, r.slug AS roleSlug FROM users AS u INNER JOIN roles AS r ON u.role_id = r.id AND u.org_id = r.org_id',
			$this->sql($sql)
		);
	}

	/**
	 * @return void
	 */
	public function testJoinTypesAndUsing(): void
	{
		$sql = (new Select('users', 'u'))
			->select('id')
			->leftJoin(['table' => 'profiles', 'alias' => 'p', 'on' => ['u.id = p.user_id']])
			->rightJoin(['table' => 'teams', 'alias' => 't', 'using' => 'USING(team_id)']);

		$this->assertSame(
			'SELECT u.id FROM users AS u LEFT JOIN profiles AS p ON u.id = p.user_id RIGHT JOIN teams AS t USING(team_id)',
			$this->sql($sql)
		);
	}

	/**
	 * @return void
	 */
	public function testEmptyJoinIsIgnored(): void
	{
		$sql = (new Select('users'))->select('id')->join([])->joins([]);
		$this->assertSame('SELECT users.id FROM users', $this->sql($sql));
	}

	/**
	 * @return void
	 */
	public function testWhereConditionsAreAndedAndGrouped(): void
	{
		$sql = (new Select('users', 'u'))
			->select('id')
			->where('u.active = 1', ['u.role', '?'], ['u.age', '>', '?'])
			->orWhere('u.a = ?', 'u.b = ?')
			->andWhere('u.c = ?', 'u.d = ?');

		$this->assertSame(
			'SELECT u.id FROM users AS u WHERE u.active = 1 AND u.role = ? AND u.age > ? AND (u.a = ? OR u.b = ?) AND (u.c = ? AND u.d = ?)',
			$this->sql($sql)
		);
	}

	/**
	 * @return void
	 */
	public function testInRendersOnePlaceholderPerValue(): void
	{
		$sql = (new Select('users'))->select('id')->in('id', [5, 6, self::EVIL]);

		$this->assertSame('SELECT users.id FROM users WHERE id IN (?,?,?)', $this->sql($sql));
		$this->assertStringNotContainsString("OR '1'", (string)$sql);
	}

	/**
	 * @return void
	 */
	public function testGroupByHavingOrderByLimit(): void
	{
		$sql = (new Select('orders', 'o'))
			->select('user_id', [['SUM(o.total)'], 'spent'])
			->groupBy('o.user_id', 'o.currency')
			->having('spent > ?')
			->orderBy(['spent', 'desc'], 'o.user_id')
			->limit(20, 10);

		$this->assertSame(
			'SELECT o.user_id, SUM(o.total) AS spent FROM orders AS o GROUP BY o.user_id, o.currency HAVING spent > ? ORDER BY spent DESC, o.user_id LIMIT 20, 10',
			$this->sql($sql)
		);
	}

	/**
	 * @return void
	 */
	public function testLimitVariants(): void
	{
		$this->assertStringEndsWith('LIMIT 5', $this->sql((new Select('a'))->select()->limit(5)));
		$this->assertStringNotContainsString('LIMIT', $this->sql((new Select('a'))->select()->limit(null, 5)));
	}

	/**
	 * @return void
	 */
	public function testDistinctAndForceIndex(): void
	{
		$sql = (new Select('users', 'u'))->distinct()->select('email')->forceIndex('idx_email');
		$this->assertSame('SELECT DISTINCT u.email FROM users AS u FORCE INDEX(idx_email)', $this->sql($sql));
	}

	/**
	 * ORDER BY / LIMIT must apply to the whole union, not the first SELECT.
	 *
	 * @return void
	 */
	public function testUnionPlacesOrderAndLimitLast(): void
	{
		$other = (new Select('archived', 'a'))->select('id');
		$sql = (new Select('users', 'u'))->select('id')->unionAll($other)->orderBy('id')->limit(5);

		$this->assertSame(
			'SELECT u.id FROM users AS u UNION ALL SELECT a.id FROM archived AS a ORDER BY id LIMIT 5',
			$this->sql($sql)
		);
	}

	/**
	 * @return void
	 */
	public function testInsertBindUsesPlaceholders(): void
	{
		$params = ['existing'];
		$sql = (new Insert('users'))->bind(['name' => self::EVIL, 'age' => 3], $params);

		$this->assertSame('INSERT INTO users (name, age) VALUES (?, ?);', $this->sql($sql));
		$this->assertSame(['existing', self::EVIL, 3], $params);
	}

	/**
	 * @return void
	 */
	public function testInsertBindCleansColumnNames(): void
	{
		$params = [];
		$sql = (new Insert('users'))->bind(['name`) VALUES (1); --' => 'a'], $params);

		$this->assertSame('INSERT INTO users (nameVALUES1) VALUES (?);', $this->sql($sql));
	}

	/**
	 * @return void
	 */
	public function testInsertFromObjectAndExplicitFields(): void
	{
		$params = [];
		$fromObject = (new Insert('t'))->bind((object)['a' => 1, 'b' => 2], $params);
		$explicit = (new Insert('t'))->fields(['a', 'b'])->values(['?', '?']);

		$this->assertSame($this->sql($explicit), $this->sql($fromObject));
		$this->assertSame([1, 2], $params);
	}

	/**
	 * @return void
	 */
	public function testInsertOnDuplicateKeyUpdateIsValidSql(): void
	{
		$params = [];
		$sql = (new Insert('stats'))
			->bind(['day' => '2026-01-01', 'hits' => 1], $params)
			->onDuplicate(['hits' => 'hits']);

		$this->assertSame(
			'INSERT INTO stats (day, hits) VALUES (?, ?) ON DUPLICATE KEY UPDATE hits = VALUES(hits);',
			$this->sql($sql)
		);
	}

	/**
	 * @return void
	 */
	public function testInsertNullDataIsNoop(): void
	{
		$sql = (new Insert('t'))->fields(['a'])->values(['?'])->bind(null)->insert(null);
		$this->assertSame('INSERT INTO t (a) VALUES (?);', $this->sql($sql));
	}

	/**
	 * @return void
	 */
	public function testReplaceBindUsesPlaceholders(): void
	{
		$params = [];
		$sql = (new Replace('users'))->bind(['id' => 1, 'name' => self::EVIL], $params);

		$this->assertSame('REPLACE INTO users (id, name) VALUES (?, ?);', $this->sql($sql));
		$this->assertSame([1, self::EVIL], $params);
	}

	/**
	 * @return void
	 */
	public function testUpdateSetWhereOrderLimit(): void
	{
		$sql = (new Update('users'))
			->set(['name' => '?', 'age' => '?'])
			->where('id = ?')
			->orderBy(['id', 'asc'])
			->limit(1);

		$this->assertSame('UPDATE users SET name = ?, age = ? WHERE id = ? ORDER BY id ASC LIMIT 1;', $this->sql($sql));
	}

	/**
	 * @return void
	 */
	public function testUpdateStringFieldsAndSetRaw(): void
	{
		$sql = (new Update('posts', 'p'))
			->update('title = ?,body = ?')
			->setRaw('p.views = p.views + 1')
			->where('p.id = ?');

		$this->assertSame(
			'UPDATE posts AS p SET title = ?, body = ?, p.views = p.views + 1 WHERE p.id = ?;',
			$this->sql($sql)
		);
	}

	/**
	 * @return void
	 */
	public function testUpdateWithJoin(): void
	{
		$sql = (new Update('posts', 'p'))
			->join(['table' => 'users', 'alias' => 'u', 'on' => ['p.user_id = u.id']])
			->set(['p.status' => '?'])
			->where('u.banned = 1');

		$this->assertSame(
			'UPDATE posts AS p INNER JOIN users AS u ON p.user_id = u.id SET p.status = ? WHERE u.banned = 1;',
			$this->sql($sql)
		);
	}

	/**
	 * @return void
	 */
	public function testDelete(): void
	{
		$sql = (new Delete('sessions'))->where('expires_at < ?', 'user_id = ?')->orderBy('id')->limit(100);
		$this->assertSame('DELETE FROM sessions WHERE expires_at < ? AND user_id = ? ORDER BY id LIMIT 100;', $this->sql($sql));
	}

	/**
	 * @return void
	 */
	public function testDeleteWithoutWhere(): void
	{
		$this->assertSame('DELETE FROM sessions ;', $this->sql(new Delete('sessions')));
	}

	/**
	 * @return void
	 */
	public function testProxyWhereBindsTupleValues(): void
	{
		$proxy = new AdapterProxy((new Select('users', 'u'))->select('id'));
		$proxy->where(['u.name', self::EVIL], ['u.age', '>=', 18], 'u.active = 1');

		$this->assertSame('SELECT u.id FROM users AS u WHERE u.name = ? AND u.age >= ? AND u.active = 1', $this->sql($proxy));
		$this->assertSame([self::EVIL, 18], $proxy->params());
	}

	/**
	 * @return void
	 */
	public function testProxyOrWhereAndInListBind(): void
	{
		$proxy = new AdapterProxy((new Select('users', 'u'))->select('id'));
		$proxy->orWhere(['u.id', 'IN', [1, 2]], ['u.email', 'LIKE', '%' . self::EVIL]);

		$this->assertSame('SELECT u.id FROM users AS u WHERE (u.id IN (?, ?) OR u.email LIKE ?)', $this->sql($proxy));
		$this->assertSame([1, 2, '%' . self::EVIL], $proxy->params());
	}

	/**
	 * @return void
	 */
	public function testProxyRejectsUnknownOperator(): void
	{
		$proxy = new AdapterProxy((new Select('users', 'u'))->select('id'));
		$proxy->where(['u.id', '= 1 OR 1 =', 5]);

		$this->assertSame('SELECT u.id FROM users AS u WHERE u.id = ?', $this->sql($proxy));
		$this->assertSame([5], $proxy->params());
	}

	/**
	 * @return void
	 */
	public function testProxySetBindsValuesAndKeepsSqlFunctions(): void
	{
		$proxy = new AdapterProxy(new Update('users', 'u'));
		$proxy->set(['displayName' => self::EVIL, 'updatedAt' => 'NOW()'])->where(['u.id', 9]);

		$this->assertSame('UPDATE users AS u SET display_name = ?, updated_at = NOW() WHERE u.id = ?;', $this->sql($proxy));
		$this->assertSame([self::EVIL, 9], $proxy->params());
	}

	/**
	 * Raw string clauses used to be wrapped in an array and rendered as
	 * "0 = <clause>".
	 *
	 * @return void
	 */
	public function testProxySetPassesRawStringClauses(): void
	{
		$proxy = new AdapterProxy(new Update('users', 'u'));
		$proxy->set('u.logins = u.logins + 1', ['lastIp' => '10.0.0.1'])->where(['u.id', 1]);

		$this->assertSame(
			'UPDATE users AS u SET u.logins = u.logins + 1, last_ip = ? WHERE u.id = ?;',
			$this->sql($proxy)
		);
		$this->assertSame(['10.0.0.1', 1], $proxy->params());
	}

	/**
	 * @return void
	 */
	public function testProxyUnknownMethodThrows(): void
	{
		$this->expectException(\BadMethodCallException::class);
		(new AdapterProxy(new Select('users')))->wher('id = 1');
	}

	/**
	 * @return void
	 */
	public function testProxyWithoutAdapterDoesNotExecute(): void
	{
		$proxy = new AdapterProxy(new Select('users'));

		$this->assertFalse($proxy->execute());
		$this->assertSame([], $proxy->fetch());
		$this->assertNull($proxy->first());
	}
}
