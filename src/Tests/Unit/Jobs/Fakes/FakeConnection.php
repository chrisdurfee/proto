<?php declare(strict_types=1);

namespace Proto\Tests\Unit\Jobs\Fakes;

use Proto\Database\Adapters\Mysqli;

/**
 * FakeConnection
 *
 * Scripted Mysqli adapter for driver tests. It never opens a link: every
 * statement is recorded, fetch() answers from a queue of canned result
 * sets, and execute() reports affected rows from a queue of counts.
 *
 * @package Proto\Tests\Unit\Jobs\Fakes
 */
class FakeConnection extends Mysqli
{
	/**
	 * @var array<int, array{0: string, 1: string, 2: mixed}> $log Recorded calls: [kind, sql/table, params/data].
	 */
	public array $log = [];

	/**
	 * @var array<int, array> $fetchResults Result sets returned by successive fetch() calls.
	 */
	public array $fetchResults = [];

	/**
	 * @var array<int, int> $affectedResults Affected-row counts for successive execute() calls (default 1).
	 */
	public array $affectedResults = [];

	/**
	 * @var bool $outerTransaction Simulates a transaction opened by someone else.
	 */
	public bool $outerTransaction = false;

	/**
	 * @var int $level Transaction level opened through this fake.
	 */
	public int $level = 0;

	/**
	 * Skips the real adapter constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * Records the statement and reports scripted affected rows.
	 *
	 * @param string $sql
	 * @param array|object $params
	 * @return bool
	 */
	public function execute(string $sql, array|object $params = []): bool
	{
		$this->log[] = ['execute', $this->squash($sql), $params];
		$this->setAffectedRows(array_shift($this->affectedResults) ?? 1);
		return true;
	}

	/**
	 * Records the query and returns the next canned result set.
	 *
	 * @param string $sql
	 * @param array|object $params
	 * @param string $resultType
	 * @return array|null
	 */
	public function fetch(string $sql, array|object $params = [], string $resultType = 'object'): ?array
	{
		$this->log[] = ['fetch', $this->squash($sql), $params];
		return array_shift($this->fetchResults) ?? [];
	}

	/**
	 * @param string $tableName
	 * @param array|object $data
	 * @return bool
	 */
	public function insert(string $tableName, array|object $data): bool
	{
		$this->log[] = ['insert', $tableName, $data];
		return true;
	}

	/**
	 * @param string $tableName
	 * @param array|object $data
	 * @param string $idColumn
	 * @return bool
	 */
	public function update(string $tableName, array|object $data, string $idColumn = 'id'): bool
	{
		$this->log[] = ['update', $tableName, $data];
		return true;
	}

	/**
	 * @param string $tableName
	 * @param int|array|string $id
	 * @param string $idColumn
	 * @return bool
	 */
	public function delete(string $tableName, int|array|string $id, string $idColumn = 'id'): bool
	{
		$this->log[] = ['delete', $tableName, $id];
		return true;
	}

	/**
	 * @return bool
	 */
	public function beginTransaction(): bool
	{
		$this->log[] = ['begin', '', null];
		$this->level++;
		return true;
	}

	/**
	 * @return bool
	 */
	public function commit(): bool
	{
		$this->log[] = ['commit', '', null];
		$this->level--;
		return true;
	}

	/**
	 * @return bool
	 */
	public function rollback(): bool
	{
		$this->log[] = ['rollback', '', null];
		$this->level--;
		return true;
	}

	/**
	 * @return int
	 */
	public function getTransactionLevel(): int
	{
		return $this->level + ($this->outerTransaction ? 1 : 0);
	}

	/**
	 * Returns the recorded call kinds in order.
	 *
	 * @return array<int, string>
	 */
	public function kinds(): array
	{
		return array_column($this->log, 0);
	}

	/**
	 * Returns recorded statements of one kind.
	 *
	 * @param string $kind
	 * @return array<int, array>
	 */
	public function calls(string $kind): array
	{
		return array_values(array_filter($this->log, fn(array $entry): bool => $entry[0] === $kind));
	}

	/**
	 * Collapses whitespace so assertions can match SQL fragments.
	 *
	 * @param string $sql
	 * @return string
	 */
	private function squash(string $sql): string
	{
		return trim((string) preg_replace('/\s+/', ' ', $sql));
	}
}
